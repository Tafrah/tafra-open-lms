<?php

declare(strict_types=1);

namespace Src\Contexts\Notifications\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * تفضيل قناة إشعار لمستخدم داخل مؤسسة.
 *
 * مش بيستخدم BelongsToTenant عن قصد: `tenant_id` هنا nullable
 * (تفضيل عام للمستخدم)، والقراءة دايماً بمفتاح مستخدم صريح.
 * مركزي في الوضعين — التفضيل ملك المستخدم مش المؤسسة.
 */
final class NotificationPreference extends Model
{
    use CentralConnection;

    /** @var list<string> */
    protected $fillable = ['user_id', 'tenant_id', 'notification_key', 'channels', 'enabled'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'enabled' => 'boolean',
        ];
    }
}
