<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Application\Actions;

use Illuminate\Support\Facades\DB;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

/**
 * حق التصدير — كل بيانات مستخدم في مكان واحد (docs/20 بند ١٣).
 *
 * الجداول مكتوبة صراحةً مش مستنتجة: جدول جديد فيه `user_id` لازم
 * يتضاف هنا بوعي، لأن «نسينا جدول» في طلب تصدير بيانات مش تفصيلة.
 *
 * الجداول نوعين (docs/03 بند ٣):
 * - مركزية: في القاعدة المركزية في الوضعين
 * - تابعة لمؤسسة: في وضع multi كل مؤسسة في قاعدة — فبنلفّ على مؤسسات
 *   المستخدم، وبنفلتر بـ `tenant_id` عشان في single مايتكررش نفس الصف
 */
final readonly class ExportUserDataAction
{
    /** الجداول المركزية اللي فيها بيانات المستخدم، وعمود الربط في كل واحد. */
    private const CENTRAL_TABLES = [
        'tenant_user' => 'user_id',
        'user_devices' => 'user_id',
        'user_preferences' => 'user_id',
        'notification_preferences' => 'user_id',
        'onboarding_progress' => 'user_id',
        'notifications' => 'notifiable_id',
        'activity_log' => 'causer_id',
    ];

    /** جداول المؤسسات اللي فيها بيانات المستخدم. */
    private const TENANT_TABLES = [
        'announcements' => 'author_id',
    ];

    public function __construct(private TenantContext $tenants) {}

    /** @return array<string, mixed> */
    public function handle(User $user): array
    {
        $data = [
            'exported_at' => now()->toIso8601String(),
            // قائمة بيضاء صريحة — `toArray()` كان هيسرّب الهاش والتوكن
            'user' => $user->only([
                'id', 'code', 'name', 'email', 'phone', 'locale',
                'status', 'is_active', 'email_verified_at', 'created_at',
            ]),
            'media' => $user->media()
                ->get(['collection_name', 'file_name', 'mime_type', 'size', 'created_at'])
                ->toArray(),
        ];

        $central = DB::connection(TenancyMode::centralConnection());

        foreach (self::CENTRAL_TABLES as $table => $column) {
            $data[$table] = $central->table($table)
                ->where($column, $user->getKey())
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all();
        }

        foreach (self::TENANT_TABLES as $table => $column) {
            $data[$table] = [];
        }

        foreach ($user->tenants()->pluck('tenants.id') as $tenantId) {
            $this->tenants->forTenant((int) $tenantId, function () use (&$data, $user, $tenantId): void {
                foreach (self::TENANT_TABLES as $table => $column) {
                    $rows = DB::table($table)
                        ->where('tenant_id', $tenantId)
                        ->where($column, $user->getKey())
                        ->get()
                        ->map(static fn (object $row): array => (array) $row)
                        ->all();

                    $data[$table] = [...$data[$table], ...$rows];
                }
            });
        }

        return $data;
    }
}
