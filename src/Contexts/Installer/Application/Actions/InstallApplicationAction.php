<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Application\Actions;

use Closure;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Src\Contexts\Installer\Application\DataObjects\AcademyOwner;
use Src\Contexts\Installer\Application\DataObjects\DatabaseCredentials;
use Src\Contexts\Installer\Application\DataObjects\InstallationData;
use Src\Contexts\Installer\Application\DataObjects\InstallationResult;
use Src\Contexts\Installer\Domain\Enums\MediaServing;
use Src\Contexts\Installer\Domain\Exceptions\AlreadyInstalledException;
use Src\Contexts\Installer\Domain\Exceptions\DatabaseConnectionFailed;
use Src\Contexts\Installer\Domain\Exceptions\InstallationFailed;
use Src\Contexts\Installer\Infrastructure\Environment\EnvironmentBlueprint;
use Src\Contexts\Installer\Infrastructure\Environment\EnvironmentFile;
use Src\Contexts\Installer\Infrastructure\InstallationState;
use Src\Contexts\Installer\Infrastructure\StorageLinker;
use Src\Support\Infrastructure\Tenancy\TenancyMode;
use Src\Support\Infrastructure\Tenancy\TenancyModeLock;
use Throwable;

/**
 * التثبيت كله — نفس الخطوات من `/install` ومن `php artisan lms:install`.
 *
 * الترتيب مقصود، وكل خطوة ممكن تتعاد لو اللي بعدها فشل:
 *
 *  1. القاعدة       — اتصال تجريبي، وبعدين الاتصال الحالي بيتحوّل عليها
 *  2. migrate       — جداول التطبيق + ميجريشنز الإعدادات (spatie بيسجّلها في نفس الـ migrator)
 *  3. الصلاحيات     — `authorization:sync` من `config/authorization.php`
 *  4. المنصة وصاحبها
 *  5. الملفات       — symlink أو بديل `/uploads`
 *  6. `.env`        — **بعد** القاعدة: لو اتكتب الأول وفشل migrate، الطلب
 *                     الجاي كان هيدوّر على جدول جلسات مش موجود
 *  7. `tenancy:init` — **بعد** `.env`: القفل بيخلّي التطبيق «متثبّت» (InstallationState)،
 *                     فلو اتقفل قبل كتابة `.env` مكانش في طريق رجوع للمثبّت
 *  8. مفاتيح OAuth (MCP) — مش مانعة
 *  9. `installed.json`
 */
final readonly class InstallApplicationAction
{
    public function __construct(
        private InstallationState $state,
        private EnvironmentFile $environment,
        private StorageLinker $linker,
        private TestDatabaseConnectionAction $probe,
        private CreateAcademyOwnerAction $createOwner,
        private DatabaseManager $db,
        private TenancyModeLock $lock,
    ) {}

    /** @param (Closure(string): void)|null $progress */
    public function handle(InstallationData $data, ?Closure $progress = null): InstallationResult
    {
        if ($this->state->isInstalled()) {
            throw AlreadyInstalledException::make();
        }

        $handle = $this->acquireLock();

        try {
            // طلبين تثبيت في نفس الوقت: التاني بيستنى القفل وبعدين يلاقي التثبيت خلص
            $this->state->forget();

            if ($this->state->isInstalled()) {
                throw AlreadyInstalledException::make();
            }

            @set_time_limit(0);
            ignore_user_abort(true);

            $this->ensureAppKey();

            if ($data->database !== null) {
                $this->step('database', $progress, fn () => $this->useDatabase($data->database));
            }

            $this->step('migrations', $progress, fn () => $this->artisan('migrate', ['--force' => true]));
            $this->step('permissions', $progress, fn () => $this->artisan('authorization:sync'));

            /** @var AcademyOwner $owner */
            $owner = $this->step('owner', $progress, fn (): AcademyOwner => $this->createOwner->handle($data->academy));

            /** @var MediaServing $media */
            $media = $this->step('storage', $progress, fn (): MediaServing => $this->linker->link());

            if ($data->writeEnvironment) {
                $this->step('environment', $progress, fn () => $this->writeEnvironment($data, $media));
            }

            $this->step('tenancy', $progress, fn () => $this->artisan('tenancy:init', ['--mode' => TenancyMode::SINGLE]));
            $this->step('keys', $progress, fn () => $this->oauthKeys());

            $this->step('finish', $progress, function () use ($media): void {
                $this->state->markInstalled([
                    'tenancy_mode' => TenancyMode::SINGLE,
                    'media_serving' => $media->value,
                    'php' => PHP_VERSION,
                ]);

                if (app()->configurationIsCached()) {
                    $this->artisan('config:clear');
                }
            });

            return new InstallationResult(
                tenantId: (int) $owner->tenant->getKey(),
                tenantSlug: (string) $owner->tenant->slug,
                adminId: (int) $owner->user->getKey(),
                mediaServing: $media,
                version: $this->state->version(),
            );
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @template T
     *
     * @param (Closure(string): void)|null $progress
     * @param Closure(): T $callback
     * @return T
     */
    private function step(string $name, ?Closure $progress, Closure $callback): mixed
    {
        if ($progress !== null) {
            $progress($name);
        }

        try {
            return $callback();
        } catch (DatabaseConnectionFailed|InstallationFailed $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $password = $this->currentPassword();
            $details = TestDatabaseConnectionAction::sanitize($exception->getMessage(), $password);

            // الرسالة بس — مش الاستثناء: تتبّعه ممكن يكون فيه معاملات الاتصال
            Log::error('installer.step_failed', ['step' => $name, 'error' => $details]);

            throw new InstallationFailed($name, $details);
        }
    }

    /**
     * التثبيت من سطر الأوامر من غير .env: مفيش APP_KEY لحد ما الملف يتكتب في الآخر،
     * بس الإعدادات المشفّرة في الميجريشنز محتاجاه من الأول — نولّد واحد ونستخدمه
     * (وهو نفسه اللي بيتكتب في .env بعدين، لأن writeEnvironment بتاخد config('app.key')).
     */
    private function ensureAppKey(): void
    {
        if (str_starts_with((string) config('app.key'), 'base64:')) {
            return;
        }

        config(['app.key' => EnvironmentBlueprint::generateKey()]);
        app()->forgetInstance('encrypter');
    }

    private function useDatabase(DatabaseCredentials $credentials): void
    {
        $this->probe->handle($credentials);

        $driver = $credentials->driver;
        $current = (array) config("database.connections.{$driver}", []);
        $wanted = $credentials->toConnectionConfig();

        $same = config('database.default') === $driver
            && (string) ($current['host'] ?? '') === $wanted['host']
            && (int) ($current['port'] ?? 0) === $wanted['port']
            && (string) ($current['database'] ?? '') === $wanted['database']
            && (string) ($current['username'] ?? '') === $wanted['username']
            && (string) ($current['password'] ?? '') === $wanted['password'];

        // نفس الاتصال (الاختبارات، أو .env مكتوب بإيد): purge كان هيقفل الـ transaction
        if ($same) {
            return;
        }

        config([
            "database.connections.{$driver}" => $wanted,
            'database.default' => $driver,
            'tenancy.database.central_connection' => $driver,
        ]);

        $this->db->purge($driver);
        $this->lock->forget();
    }

    private function writeEnvironment(InstallationData $data, MediaServing $media): void
    {
        $existing = $this->environment->read();
        $key = (string) ($existing['APP_KEY'] ?? '');

        if (! str_starts_with($key, 'base64:')) {
            $key = (string) config('app.key') ?: EnvironmentBlueprint::generateKey();
        }

        $url = rtrim($data->appUrl, '/');
        $prefix = (string) config('installer.public_files.prefix');

        $database = $data->database?->toEnvironment() ?? [
            'DB_CONNECTION' => (string) config('database.default'),
        ];

        $this->environment->write(EnvironmentBlueprint::installed(
            key: $key,
            name: $data->academy->platformName,
            url: $url,
            database: $database,
            mailFrom: $data->academy->adminEmail,
            secureCookies: $data->secureCookies,
            publicStorageUrl: $media === MediaServing::Route ? "{$url}/{$prefix}" : null,
            version: $this->state->version(),
        ));
    }

    private function oauthKeys(): void
    {
        if (is_file(storage_path('oauth-private.key')) || (string) config('passport.private_key') !== '') {
            return;
        }

        try {
            Artisan::call('passport:keys', ['--force' => true]);
        } catch (Throwable $exception) {
            // MCP بس اللي محتاجها — المنصة نفسها شغّالة من غيرها
            Log::warning('installer.oauth_keys_skipped', ['error' => $exception->getMessage()]);
        }
    }

    /** @param array<string, mixed> $parameters */
    private function artisan(string $command, array $parameters = []): void
    {
        $status = Artisan::call($command, $parameters);

        if ($status !== 0) {
            throw new \RuntimeException(trim(Artisan::output()));
        }
    }

    /** @return resource */
    private function acquireLock()
    {
        $path = (string) config('installer.lock_path');

        if (! is_dir(dirname($path))) {
            @mkdir(dirname($path), 0775, true);
        }

        $handle = @fopen($path, 'c');

        if ($handle === false || ! flock($handle, LOCK_EX | LOCK_NB)) {
            throw new InstallationFailed('lock');
        }

        return $handle;
    }

    private function currentPassword(): string
    {
        return (string) config('database.connections.'.config('database.default').'.password', '');
    }
}
