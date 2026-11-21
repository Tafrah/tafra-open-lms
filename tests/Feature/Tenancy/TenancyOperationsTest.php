<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Spatie\Health\Enums\Status;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Contexts\Tenancy\Infrastructure\Backup\RegisterTenantBackupDatabases;
use Src\Contexts\Tenancy\Infrastructure\Health\TenancyModeCheck;
use Src\Contexts\Tenancy\Infrastructure\Health\TenantDatabasesCheck;
use Src\Support\Infrastructure\Tenancy\TenancyMode;
use Src\Support\Infrastructure\Tenancy\TenancyModeLock;

/*
|--------------------------------------------------------------------------
| التشغيل: فحوصات الصحة والنسخ الاحتياطي في الوضعين (docs/14 بند ٣)
|--------------------------------------------------------------------------
*/

/** بيسجّل وضع مؤقت في `system_state` وبيمسحه بعد الاختبار — الجدول مستثنى من التفريغ في multi. */
function withStoredTenancyMode(string $mode, callable $callback): void
{
    $lock = app(TenancyModeLock::class);
    $table = (string) config('tenancy.lock.table');
    $key = (string) config('tenancy.lock.key');
    $central = DB::connection((string) config('tenancy.database.central_connection'));

    $central->table($table)->updateOrInsert(['key' => $key], ['value' => $mode, 'updated_at' => now(), 'created_at' => now()]);
    $lock->forget();

    try {
        $callback();
    } finally {
        $central->table($table)->where('key', $key)->delete();
        $lock->forget();
    }
}

it('فحص الوضع بينبّه لو التثبيت مش مقفول', function (): void {
    app(TenancyModeLock::class)->forget();

    expect(TenancyModeCheck::new()->run()->status)->toBe(Status::warning());
});

it('فحص الوضع أخضر لما الوضع المقفول مطابق للبيئة', function (): void {
    withStoredTenancyMode(TenancyMode::current(), function (): void {
        expect(TenancyModeCheck::new()->run()->status)->toBe(Status::ok());
    });
});

it('فحص الوضع أحمر لما الوضع المقفول مختلف', function (): void {
    withStoredTenancyMode(TenancyMode::isMulti() ? 'single' : 'multi', function (): void {
        expect(TenancyModeCheck::new()->run()->status)->toBe(Status::failed());
    });
});

it('فحص قواعد المؤسسات بيمسك قاعدة ناقصة', function (): void {
    $healthy = Tenant::factory()->create();
    $broken = Tenant::factory()->create();

    expect(TenantDatabasesCheck::new()->run()->status)->toBe(Status::ok());

    tenancy()->end();
    DB::connection((string) config('tenancy.database.central_connection'))
        ->statement(sprintf('DROP DATABASE IF EXISTS "%s" WITH (FORCE)', $broken->database()->getName()));

    $result = TenantDatabasesCheck::new()->run();

    expect($result->status)->toBe(Status::failed())
        ->and($result->meta['broken'])->toBe([$broken->slug])
        ->and($result->meta['broken'])->not->toContain($healthy->slug);
})->skip(fn (): bool => ! TenancyMode::isMulti(), 'قاعدة لكل مؤسسة بس');

it('النسخ الاحتياطي بيشمل قاعدة كل مؤسسة', function (): void {
    $tenant = Tenant::factory()->create();

    app(RegisterTenantBackupDatabases::class)();

    $central = (string) config('tenancy.database.central_connection');
    $connection = 'tenant_backup_'.$tenant->getKey();

    expect(config('backup.backup.source.databases'))->toContain($central)
        ->and(config('backup.backup.source.databases'))->toContain($connection)
        ->and(config("database.connections.{$connection}.database"))->toBe($tenant->database()->getName());
})->skip(fn (): bool => ! TenancyMode::isMulti(), 'قاعدة لكل مؤسسة بس');
