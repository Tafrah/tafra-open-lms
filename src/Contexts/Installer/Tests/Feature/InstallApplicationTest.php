<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Installer\Application\Actions\InstallApplicationAction;
use Src\Contexts\Installer\Application\DataObjects\InstallationData;
use Src\Contexts\Installer\Application\DataObjects\InstallationResult;
use Src\Contexts\Installer\Domain\Enums\MediaServing;
use Src\Contexts\Installer\Domain\Exceptions\AlreadyInstalledException;
use Src\Contexts\Installer\Infrastructure\InstallationState;
use Src\Contexts\Settings\Domain\Settings\GeneralSettings;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Authorization\CentralRoles;
use Src\Support\Infrastructure\Tenancy\TenancyModeLock;

require_once __DIR__.'/../helpers.php';

beforeEach(function (): void {
    $this->dir = freshInstallerSandbox();
});

function runInstall(array $academy = [], bool $writeEnvironment = true): InstallationResult
{
    $steps = [];

    $result = app(InstallApplicationAction::class)->handle(new InstallationData(
        academy: academyData($academy),
        appUrl: 'https://academy.example.com/',
        database: testDatabaseCredentials(),
        writeEnvironment: $writeEnvironment,
        secureCookies: true,
    ), static function (string $step) use (&$steps): void {
        $steps[] = $step;
    });

    test()->steps = $steps;

    return $result;
}

it('بيثبّت المنصة كاملة: المؤسسة، المدير، الأدوار، الإعدادات، .env، القفل، العلامة', function (): void {
    $result = runInstall();

    // الخطوات بالترتيب — .env قبل قفل الوضع (راجع InstallApplicationAction)
    expect($this->steps)->toBe(['database', 'migrations', 'permissions', 'owner', 'storage', 'environment', 'tenancy', 'keys', 'finish']);

    $tenant = Tenant::query()->findOrFail($result->tenantId);
    expect($tenant->getTranslation('name', 'ar'))->toBe('أكاديمية النجاح')
        ->and($tenant->getTranslation('description', 'ar'))->toBe('الفيزياء')
        ->and($tenant->slug)->toBe('academy')
        ->and($tenant->isProvisioned())->toBeTrue();

    $user = User::query()->findOrFail($result->adminId);
    expect($user->email)->toBe('teacher@example.com')
        ->and($user->name)->toBe('أ. محمد أحمد')
        ->and($user->phone)->toBe('+20 100 123 4567')
        ->and($user->is_active)->toBeTrue()
        ->and(Hash::check('Secret123', $user->password))->toBeTrue()
        ->and($user->tenants()->whereKey($tenant->getKey())->exists())->toBeTrue();

    // دور admin على فريق المؤسسة — spatie teams
    $isAdmin = app(TenantContext::class)->forTenant((int) $tenant->getKey(), static function () use ($user): bool {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh()->roles->pluck('name')->contains('admin');
    });
    expect($isAdmin)->toBeTrue();

    // وsuper_admin على الفريق المركزي — صاحب التثبيت كله
    app(CentralRoles::class)->forget();
    expect(app(CentralRoles::class)->isSuperAdmin($user))->toBeTrue();

    $general = app(GeneralSettings::class);
    expect($general->app_name['ar'])->toBe('أكاديمية النجاح')
        ->and($general->support_email)->toBe('teacher@example.com');

    expect(app(TenancyModeLock::class)->stored())->toBe('single');

    $marker = json_decode((string) file_get_contents($this->dir.'/installed.json'), true);
    expect($marker)->toHaveKeys(['version', 'installed_at', 'tenancy_mode', 'media_serving'])
        ->and($marker['media_serving'])->toBe($result->mediaServing->value)
        ->and(app(InstallationState::class)->isInstalled())->toBeTrue();
});

it('بيكتب .env للاستضافة المشتركة — قاعدة، كاش وجلسات وطوابير على القاعدة، إنتاج من غير debug', function (): void {
    runInstall();

    $env = Dotenv::parse((string) file_get_contents($this->dir.'/.env'));
    $db = testDatabaseCredentials();

    expect($env)->toMatchArray([
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'APP_URL' => 'https://academy.example.com',
        'APP_NAME' => 'أكاديمية النجاح',
        'DB_CONNECTION' => $db->driver,
        'DB_HOST' => $db->host,
        'DB_DATABASE' => $db->database,
        'DB_USERNAME' => $db->username,
        'DB_PASSWORD' => $db->password,
        'TENANCY_MODE' => 'single',
        'CACHE_STORE' => 'database',
        'QUEUE_CONNECTION' => 'database',
        'SESSION_DRIVER' => 'database',
        'SESSION_SECURE_COOKIE' => 'true',
        'BROADCAST_CONNECTION' => 'log',
        'PULSE_ENABLED' => 'false',
        'PERMISSION_CACHE_STORE' => 'database',
        'SETTINGS_CACHE_STORE' => 'database',
        'MAIL_MAILER' => 'log',
        'MAIL_FROM_ADDRESS' => 'teacher@example.com',
    ])->and($env['APP_KEY'])->toStartWith('base64:')
        ->and(strlen((string) base64_decode(substr((string) $env['APP_KEY'], 7), true)))->toBe(32);
});

it('بيحافظ على APP_KEY الموجود — الجلسات والكوكيز اللي اتعملت قبل التثبيت تفضل شغّالة', function (): void {
    file_put_contents($this->dir.'/.env', 'APP_KEY=base64:'.base64_encode(str_repeat('k', 32))."\n");

    runInstall();

    expect(Dotenv::parse((string) file_get_contents($this->dir.'/.env'))['APP_KEY'])
        ->toBe('base64:'.base64_encode(str_repeat('k', 32)));
});

it('symlink() مش متاح = الملفات من /uploads و PUBLIC_STORAGE_URL بيشاور عليه', function (): void {
    // مكان الرابط محجوز بملف عادي — نفس نتيجة استضافة قافلة symlink()
    file_put_contents($this->dir.'/public/storage', '');

    $result = runInstall();

    expect($result->mediaServing)->toBe(MediaServing::Route)
        ->and(Dotenv::parse((string) file_get_contents($this->dir.'/.env'))['PUBLIC_STORAGE_URL'] ?? null)
        ->toBe('https://academy.example.com/uploads');
});

it('symlink() متاح = رابط public/storage ومفيش PUBLIC_STORAGE_URL', function (): void {
    $result = runInstall();

    expect($result->mediaServing)->toBe(MediaServing::Symlink)
        ->and(is_link($this->dir.'/public/storage'))->toBeTrue()
        ->and(Dotenv::parse((string) file_get_contents($this->dir.'/.env')))->not->toHaveKey('PUBLIC_STORAGE_URL');
});

it('مابيتعادش بعد ما يخلص', function (): void {
    runInstall();

    expect(fn () => runInstall())->toThrow(AlreadyInstalledException::class);
});

it('في Docker (القيم من البيئة) مابيكتبش ملف الإعدادات', function (): void {
    runInstall(writeEnvironment: false);

    expect(is_file($this->dir.'/.env'))->toBeFalse()
        ->and($this->steps)->not->toContain('environment');
});

it('إعادة التثبيت بعد فشل جزئي مابتعملش مؤسسة تانية', function (): void {
    $first = Tenant::factory()->create(['slug' => 'existing']);

    $result = runInstall();

    expect($result->tenantId)->toBe((int) $first->getKey())
        ->and(Tenant::query()->count())->toBe(1);
});

it('الطلب من الفورم بيثبّت ويعرض صفحة النهاية بسطر الكرون', function (): void {
    $credentials = testDatabaseCredentials();

    $this->withSession(['installer.database' => [...$credentials->withoutPassword(), 'db_password' => $credentials->password]])
        ->post('/install/academy', academyInput(['admin_password_confirmation' => 'Secret123']))
        ->assertOk()
        ->assertSee(__('installer::installer.finished.title'))
        ->assertSee('schedule:run')
        ->assertSee(base_path('artisan'))
        ->assertSessionMissing('installer.database');

    expect(User::query()->where('email', 'teacher@example.com')->exists())->toBeTrue();

    // بعد التثبيت المثبّت اختفى
    $this->get('/install')->assertNotFound();
});

it('الفورم بيرفض كلمة سر ضعيفة أو مش متطابقة', function (array $overrides, string $field): void {
    $credentials = testDatabaseCredentials();

    $this->withSession(['installer.database' => [...$credentials->withoutPassword(), 'db_password' => $credentials->password]])
        ->post('/install/academy', academyInput($overrides))
        ->assertSessionHasErrors($field);

    expect(app(InstallationState::class)->isInstalled())->toBeFalse();
})->with([
    'ضعيفة' => [['admin_password' => 'short', 'admin_password_confirmation' => 'short'], 'admin_password'],
    'مش متطابقة' => [['admin_password_confirmation' => 'Other1234'], 'admin_password'],
    'بريد غلط' => [['admin_email' => 'not-an-email', 'admin_password_confirmation' => 'Secret123'], 'admin_email'],
    'من غير اسم' => [['platform_name' => '', 'admin_password_confirmation' => 'Secret123'], 'platform_name'],
]);
