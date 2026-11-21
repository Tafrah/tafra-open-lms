<?php

declare(strict_types=1);

use Src\Contexts\Installer\Application\Actions\TestDatabaseConnectionAction;
use Src\Contexts\Installer\Application\DataObjects\DatabaseCredentials;
use Src\Contexts\Installer\Domain\Exceptions\DatabaseConnectionFailed;

require_once __DIR__.'/../helpers.php';

beforeEach(function (): void {
    freshInstallerSandbox();
});

/** @return array<string, string|int> */
function databaseForm(?string $password = null): array
{
    $credentials = testDatabaseCredentials($password);

    return [...$credentials->withoutPassword(), 'db_password' => $credentials->password];
}

it('الحقول المطلوبة ليها رسائل تحقق', function (): void {
    $this->from('/install/database')
        ->post('/install/database', [])
        ->assertRedirect('/install/database')
        ->assertSessionHasErrors(['db_host', 'db_port', 'db_database', 'db_username', 'db_driver']);
});

it('نوع قاعدة غير مدعوم مرفوض', function (): void {
    $this->post('/install/database', [...databaseForm(), 'db_driver' => 'sqlite'])
        ->assertSessionHasErrors('db_driver');
});

it('اتصال صحيح بيحفظ البيانات في الجلسة ويروح لخطوة المنصة', function (): void {
    $this->post('/install/database', databaseForm())
        ->assertRedirect('/install/academy')
        ->assertSessionHas('installer.database.db_database', config('database.connections.mysql.database'));
});

it('كلمة سر غلط = رسالة عربية واضحة ومن غير ما ترجع كلمة السر للفورم', function (): void {
    app()->setLocale('ar');

    $response = $this->from('/install/database')
        ->post('/install/database', databaseForm('definitely-wrong-password'));

    $response->assertRedirect('/install/database')
        ->assertSessionHasErrors(['database' => __('installer::installer.database.errors.access_denied')]);

    expect(session()->getOldInput('db_password'))->toBeNull()
        ->and((string) session('installer.details'))->not->toContain('definitely-wrong-password');
});

it('قاعدة مش موجودة', function (): void {
    $credentials = testDatabaseCredentials();

    $missing = new DatabaseCredentials($credentials->driver, $credentials->host, $credentials->port, 'no_such_db_'.bin2hex(random_bytes(3)), $credentials->username, $credentials->password);

    expect(fn () => app(TestDatabaseConnectionAction::class)->handle($missing))
        ->toThrow(DatabaseConnectionFailed::class, __('installer::installer.database.errors.unknown_database'));
});

it('بيرجّع إصدار السيرفر ويتأكد إن المستخدم يقدر يعمل جداول', function (): void {
    $version = app(TestDatabaseConnectionAction::class)->handle(testDatabaseCredentials());

    expect($version)->not->toBeEmpty()
        ->and(config('database.connections.installer_probe'))->toBeNull();
});

it('بيترجم رسائل MySQL و PostgreSQL للأسباب المفهومة', function (string $message, string $reason): void {
    expect(TestDatabaseConnectionAction::reason($message))->toBe($reason);
})->with([
    ["SQLSTATE[HY000] [1045] Access denied for user 'x'@'localhost' (using password: YES)", 'access_denied'],
    ["SQLSTATE[HY000] [1044] Access denied for user 'x'@'%' to database 'lms'", 'no_database_access'],
    ["SQLSTATE[HY000] [1049] Unknown database 'lms'", 'unknown_database'],
    ['SQLSTATE[HY000] [2002] Connection refused', 'unreachable'],
    ['SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo for db failed', 'unreachable'],
    ['SQLSTATE[42000]: Syntax error or access violation: 1142 CREATE command denied to user', 'no_create_privilege'],
    ['SQLSTATE[08006] [7] FATAL:  password authentication failed for user "lms"', 'access_denied'],
    ['SQLSTATE[08006] [7] FATAL:  database "lms" does not exist', 'unknown_database'],
    ['could not find driver', 'driver_missing'],
    ['something odd', 'unknown'],
]);

it('كلمة السر بتتشال من أي رسالة خطأ', function (): void {
    expect(TestDatabaseConnectionAction::sanitize("failed for p@ss\$word\nSQL: select", 'p@ss$word'))
        ->toBe('failed for ********');
});

it('كلمة السر مش ظاهرة في dump للبيانات', function (): void {
    expect(print_r(testDatabaseCredentials('top-secret'), true))->not->toContain('top-secret');
});
