<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Domain\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use Src\Contexts\Identity\Database\Factories\UserFactory;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Infrastructure\Persistence\Concerns\InteractsWithResolvedMedia;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\TenantCollection;

final class User extends Authenticatable implements FilamentUser, HasAvatar, HasLocalePreference, HasMedia, HasName, HasTenants, OAuthenticatable
{
    // المستخدمين مركزيين في الوضعين — نفس الحساب بيدخل أكتر من مؤسسة (docs/03 بند ٣)
    use CentralConnection;

    // توكنز OAuth لتطبيقات الذكاء الاصطناعي (docs/23) — الربط بالمؤسسة في `AiConnection`
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use InteractsWithMedia;
    use InteractsWithResolvedMedia;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * قائمة بيضاء صريحة. `$guarded = []` ممنوع منعاً باتاً.
     * لاحظ إن حقول الحالة والصلاحيات مش هنا — بتتحط برمجياً.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'locale', 'timezone',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    // ═══════════════════════ العلاقات ═══════════════════════

    /** @return BelongsToMany<Tenant, $this> */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')->withPivot('joined_at');
    }

    /** @return HasMany<UserDevice, $this> */
    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    /** @return HasMany<UserPreference, $this> */
    public function preferences(): HasMany
    {
        return $this->hasMany(UserPreference::class);
    }

    /** @return HasMany<UserPasswordHistory, $this> */
    public function passwordHistories(): HasMany
    {
        return $this->hasMany(UserPasswordHistory::class);
    }

    // ═══════════════════════ Filament ═══════════════════════

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && Gate::allows('access.panel.'.$panel->getId());
    }

    /** stancl بيرجّع مجموعاته الخاصة للمؤسسات — وهي Collection عادية فوق كده. */
    public function getTenants(Panel $panel): TenantCollection
    {
        return $this->tenants()->where('is_active', true)->whereNull('suspended_at')->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->tenants()->whereKey($tenant->getKey())->exists();
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb') ?: null;
    }

    // ═══════════════════════ الترجمة والإشعارات ═══════════════════════

    /** Laravel بيلفّ إرسال الإشعار في اللغة دي تلقائياً. */
    public function preferredLocale(): string
    {
        return $this->locale ?? (string) config('app.locale');
    }

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'users.'.$this->getKey();
    }

    // ═══════════════════════ الوسائط ═══════════════════════

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // الترتيب مقصود: `fit()` بترجّع الـ ImageDriver مش التحويل،
        // فأي إعداد خاص بالتحويل لازم ييجي قبلها.
        $this->addMediaConversion('thumb')
            ->performOnCollections('avatar')
            ->queued()
            ->fit(Fit::Crop, 128, 128)
            ->format('webp')
            ->quality(82);
    }

    // ═══════════════════════ سجل النشاط ═══════════════════════

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'status', 'is_active', 'locale'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('identity');
    }

    // ═══════════════════════ استعلامات ═══════════════════════

    /**
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('status', UserStatus::Active);
    }

    /**
     * @param Builder<User> $query
     * @return Builder<User>
     */
    public function scopeOfTenant(Builder $query, int $tenantId): Builder
    {
        return $query->whereHas('tenants', fn (Builder $q) => $q->whereKey($tenantId));
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
