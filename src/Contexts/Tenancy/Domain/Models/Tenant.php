<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Translatable\HasTranslations;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Database\Factories\TenantFactory;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * المؤسسة (المستأجر). كل بيانات التطبيق تابعة لواحدة منهم.
 *
 * الموديل ده نفسه **مش** تابع لمستأجر — هو الجذر، ودايماً على
 * الاتصال المركزي (`CentralConnection` من الأب).
 *
 * بيرث من stancl عشان يشتغل في الوضعين (docs/03 بند ١):
 * - single: stancl بيبدّل الصلاحيات والكاش والطابور بس
 * - multi: وكمان بيبدّل الاتصال لقاعدة المؤسسة (`HasDatabase`)
 *
 * أي خاصية مش في `getCustomColumns()` بتتخزّن في عمود `data` —
 * منها بيانات القاعدة الداخلية (`tenancy_db_name`).
 */
final class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;

    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    use HasTranslations;
    use LogsActivity;
    use SoftDeletes;

    public const PROVISIONING_PENDING = 'pending';

    public const PROVISIONING_READY = 'ready';

    public const PROVISIONING_FAILED = 'failed';

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    /** @var list<string> */
    protected $fillable = [
        'slug', 'domain', 'name', 'description',
        'logo_path', 'primary_color', 'is_active', 'trial_ends_at',
    ];

    /** @return list<string> */
    public static function getCustomColumns(): array
    {
        return [
            'id', 'slug', 'domain', 'name', 'description', 'logo_path', 'primary_color',
            'is_active', 'trial_ends_at', 'provisioning_status', 'suspended_at', 'suspension_reason',
            'created_at', 'updated_at', 'deleted_at',
        ];
    }

    /** مفاتيح أرقام متسلسلة — مش UUID زي افتراضي stancl. */
    public function getIncrementing(): bool
    {
        return true;
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'tenant_user',
        )->withPivot('joined_at');
    }

    /**
     * @param Builder<Tenant> $query
     * @return Builder<Tenant>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNull('suspended_at');
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function isProvisioned(): bool
    {
        return $this->provisioning_status === self::PROVISIONING_READY;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['slug', 'name', 'domain', 'is_active', 'primary_color', 'suspended_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('tenancy');
    }

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }
}
