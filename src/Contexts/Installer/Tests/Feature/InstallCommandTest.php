<?php

declare(strict_types=1);

use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Installer\Infrastructure\InstallationState;

require_once __DIR__.'/../helpers.php';

beforeEach(function (): void {
    $this->dir = freshInstallerSandbox();
});

/** @return array<string, string> */
function commandOptions(array $overrides = []): array
{
    return [
        '--no-interaction' => true,
        '--skip-env' => true,
        '--url' => 'https://lms.example.com',
        '--platform-name' => 'Future Academy',
        '--teacher-name' => 'Mona Ali',
        '--subject' => 'Chemistry',
        '--admin-email' => 'mona@example.com',
        '--admin-password' => 'generated-secret-x7',
        ...$overrides,
    ];
}

it('بيثبّت من غير واجهة (Docker / Coolify)', function (): void {
    $this->artisan('lms:install', commandOptions())->assertSuccessful();

    expect(User::query()->where('email', 'mona@example.com')->exists())->toBeTrue()
        ->and(app(InstallationState::class)->isInstalled())->toBeTrue()
        ->and(is_file($this->dir.'/.env'))->toBeFalse();
});

it('بيقبل بيانات القاعدة كخيارات ويكتب .env', function (): void {
    $db = testDatabaseCredentials();

    $this->artisan('lms:install', commandOptions([
        '--skip-env' => false,
        '--db-driver' => $db->driver,
        '--db-host' => $db->host,
        '--db-port' => (string) $db->port,
        '--db-database' => $db->database,
        '--db-username' => $db->username,
        '--db-password' => $db->password,
    ]))->assertSuccessful();

    expect((string) file_get_contents($this->dir.'/.env'))->toContain('DB_DATABASE='.$db->database);
});

it('خيارات ناقصة مع --no-interaction = فشل برسائل تحقق', function (): void {
    $this->artisan('lms:install', commandOptions(['--admin-email' => '', '--admin-password' => 'weak']))
        ->assertFailed();

    expect(app(InstallationState::class)->isInstalled())->toBeFalse();
});

it('متثبّت بالفعل = فشل، إلا مع --if-not-installed', function (): void {
    file_put_contents($this->dir.'/installed.json', '{}');

    $this->artisan('lms:install', commandOptions())->assertFailed();
    $this->artisan('lms:install', commandOptions(['--if-not-installed' => true]))->assertSuccessful();
});

it('بيانات قاعدة غلط = فشل برسالة مفهومة', function (): void {
    $db = testDatabaseCredentials();

    $this->artisan('lms:install', commandOptions([
        '--skip-env' => false,
        '--db-driver' => $db->driver,
        '--db-host' => $db->host,
        '--db-port' => (string) $db->port,
        '--db-database' => $db->database,
        '--db-username' => $db->username,
        '--db-password' => 'definitely-wrong-password',
    ]))->expectsOutputToContain(__('installer::installer.database.errors.access_denied'))->assertFailed();
});
