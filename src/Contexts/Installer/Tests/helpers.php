<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Src\Contexts\Installer\Application\DataObjects\AcademyData;
use Src\Contexts\Installer\Application\DataObjects\DatabaseCredentials;
use Src\Contexts\Installer\Infrastructure\Environment\EnvironmentFile;
use Src\Contexts\Installer\Infrastructure\InstallationState;
use Src\Contexts\Installer\Infrastructure\StorageLinker;

/*
| مساعدات اختبارات المثبّت.
|
| كل حاجة بتتكتب على الديسك (`.env`، العلامة، القفل، symlink) بتروح لمجلد
| مؤقت — الاختبار مايلمسش `.env` بتاع المطوّر ولا `public/storage`.
*/

/** بيقفل «متثبّت» الافتراضي في الاختبارات ويحوّل كل المسارات لمجلد مؤقت. */
function freshInstallerSandbox(): string
{
    $dir = sys_get_temp_dir().'/lms-installer-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($dir.'/public');
    File::ensureDirectoryExists($dir.'/storage/app/public');

    config([
        'installer.assume_installed' => false,
        'installer.marker_path' => $dir.'/installed.json',
        'installer.lock_path' => $dir.'/installer.lock',
        'installer.version_file' => $dir.'/VERSION',
        // مفاتيح OAuth موجودة «من البيئة» — الاختبار مايولّدش ملفات في storage/
        'passport.private_key' => 'test-key',
    ]);

    app()->instance(EnvironmentFile::class, new EnvironmentFile($dir.'/.env'));
    app()->instance(StorageLinker::class, new StorageLinker($dir.'/public/storage', $dir.'/storage/app/public'));
    app(InstallationState::class)->forget();

    return $dir;
}

/** نفس القاعدة اللي الاختبارات شغّالة عليها — مفيش purge للاتصال. */
function testDatabaseCredentials(?string $password = null): DatabaseCredentials
{
    $connection = (array) config('database.connections.'.config('database.default'));

    return new DatabaseCredentials(
        driver: (string) config('database.default'),
        host: (string) $connection['host'],
        port: (int) $connection['port'],
        database: (string) $connection['database'],
        username: (string) $connection['username'],
        password: $password ?? (string) ($connection['password'] ?? ''),
    );
}

/** @return array<string, string> */
function academyInput(array $overrides = []): array
{
    return [
        'platform_name' => 'أكاديمية النجاح',
        'teacher_name' => 'أ. محمد أحمد',
        'subject' => 'الفيزياء',
        'admin_email' => 'Teacher@Example.com',
        'admin_phone' => '+20 100 123 4567',
        'admin_password' => 'Secret123',
        ...$overrides,
    ];
}

function academyData(array $overrides = []): AcademyData
{
    return AcademyData::fromArray(academyInput($overrides));
}
