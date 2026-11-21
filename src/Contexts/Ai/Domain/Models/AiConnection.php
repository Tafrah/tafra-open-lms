<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Passport\Client;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Src\Contexts\Ai\Domain\Enums\AiAccessLevel;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * تطبيق ذكاء اصطناعي مربوط بحساب مستخدم — في مؤسسة واحدة.
 *
 * ⚠️ مش `BelongsToTenant` عن قصد: الربط بيتقرا **قبل** ما نعرف المؤسسة
 * (هو اللي بيحددها)، و`tenant_id = null` معناه اللوحة المركزية. العزل هنا
 * بالمالك (`user_id`) وبالـ Policy — زي `UserPreference` (IsolationTest).
 *
 * @property int $id
 * @property int $user_id
 * @property string $oauth_client_id
 * @property int|null $tenant_id
 * @property AiAccessLevel $access
 * @property Carbon|null $last_used_at
 * @property Carbon|null $revoked_at
 */
final class AiConnection extends Model
{
    use CentralConnection;
    use LogsActivity;

    /**
     * `user_id` و`oauth_client_id` بيتحطوا من الـ Action — مش من أي مدخل.
     *
     * @var list<string>
     */
    protected $fillable = ['tenant_id', 'access', 'last_used_at', 'revoked_at'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'oauth_client_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    public function isCentral(): bool
    {
        return $this->tenant_id === null;
    }

    /**
     * @param Builder<AiConnection> $query
     * @return Builder<AiConnection>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tenant_id', 'access', 'revoked_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('mcp');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'access' => AiAccessLevel::class,
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
