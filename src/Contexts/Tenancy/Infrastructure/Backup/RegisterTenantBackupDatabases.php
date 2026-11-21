<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Infrastructure\Backup;

use Illuminate\Support\Facades\Schema;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

/**
 * في وضع multi، النسخ الاحتياطي لازم يشمل **قاعدة كل مؤسسة** — مش المركزية بس.
 *
 * `spatie/laravel-backup` بياخد أسماء **اتصالات** من `config/database.php`،
 * وقواعد المؤسسات مالهاش اتصالات ثابتة. فبنسجّل اتصال لكل مؤسسة وقت
 * تشغيل أوامر `backup:*` بس (docs/14 بند ٣) — الطلبات العادية مابتدفعش
 * تمن الاستعلام ده.
 *
 * نسخة `single` مش محتاجة حاجة: كل البيانات في القاعدة المركزية.
 */
final class RegisterTenantBackupDatabases
{
    private const CONNECTION_PREFIX = 'tenant_backup_';

    public function __invoke(): void
    {
        if (! TenancyMode::isMulti()) {
            return;
        }

        $central = (string) config('tenancy.database.central_connection');

        if (! Schema::connection($central)->hasTable('tenants')) {
            return;
        }

        /** @var array<string, mixed> $base */
        $base = (array) config("database.connections.{$central}");
        $connections = [$central];

        // المحذوفة حذف ناعم لسه ليها قاعدة — والاستعادة محتاجة نسختها
        // قاعدة مش جاهزة مش موجودة أصلاً — dump عليها كان بيفشّل النسخة كلها
        Tenant::query()->withTrashed()->where('provisioning_status', Tenant::PROVISIONING_READY)->orderBy('id')->each(function (Tenant $tenant) use ($base, &$connections): void {
            $name = self::CONNECTION_PREFIX.$tenant->getKey();

            config(["database.connections.{$name}" => [...$base, 'database' => $tenant->database()->getName()]]);

            $connections[] = $name;
        });

        config(['backup.backup.source.databases' => $connections]);
    }
}
