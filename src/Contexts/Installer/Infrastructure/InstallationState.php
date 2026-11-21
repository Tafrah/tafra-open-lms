<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Infrastructure;

use Illuminate\Support\Facades\DB;
use Src\Support\Infrastructure\Tenancy\TenancyModeLock;
use Throwable;

/**
 * التطبيق متثبّت ولا لأ؟
 *
 * المصدر الأساسي ملف `storage/app/installed.json` — آخر خطوة في التثبيت
 * بتكتبه. تثبيت قديم من قبل المثبّت (`deploy.sh`، أو بيئة تطوير) مالوش
 * الملف بس وضعه مقفول (`tenancy:init`) — بنعتبره متثبّت ونكتب الملف.
 *
 * ⚠️ الإجابة الإيجابية بس اللي بتتخزّن: «مش متثبّت» لازم تتسأل تاني بعد
 * ما التثبيت يخلص في نفس العملية.
 */
final class InstallationState
{
    private bool $installed = false;

    public function __construct(private readonly TenancyModeLock $lock) {}

    public function isInstalled(): bool
    {
        if ((bool) config('installer.assume_installed')) {
            return true;
        }

        if ($this->installed || is_file($this->markerPath())) {
            return $this->installed = true;
        }

        if ($this->lock->isLocked()) {
            $this->markInstalled(['legacy' => true]);

            return $this->installed = true;
        }

        // ملف العلامة ضاع والقاعدة مش بترد لحظيًا؟ لو .env فيه قاعدة متظبطة يبقى الموقع
        // متثبّت ووقع — نقفل (صفحة خطأ) بدل ما نفتح المثبّت لأي حد على موقع شغّال
        return $this->databaseConfiguredButUnreachable();
    }

    private function databaseConfiguredButUnreachable(): bool
    {
        $env = app()->environmentFilePath();

        if (! is_file($env) || preg_match('/^DB_DATABASE=\S+/m', (string) file_get_contents($env)) !== 1) {
            return false;
        }

        try {
            DB::connection()->getPdo();

            return false;
        } catch (Throwable) {
            return true;
        }
    }

    /** @param array<string, mixed> $details */
    public function markInstalled(array $details = []): void
    {
        $path = $this->markerPath();

        if (! is_dir(dirname($path))) {
            @mkdir(dirname($path), 0775, true);
        }

        $payload = [
            'version' => $this->version(),
            'installed_at' => now()->toIso8601String(),
            ...$details,
        ];

        // تثبيت قديم على مجلد مش قابل للكتابة: الإجابة بتفضل صح من القفل
        @file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);

        $this->installed = true;
    }

    /** @return array<string, mixed>|null */
    public function marker(): ?array
    {
        if (! is_file($this->markerPath())) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($this->markerPath()), true);

        return is_array($decoded) ? $decoded : null;
    }

    public function version(): string
    {
        $file = (string) config('installer.version_file');

        if ($file !== '' && is_file($file)) {
            $version = trim((string) file_get_contents($file));

            if ($version !== '') {
                return $version;
            }
        }

        return (string) config('app.version');
    }

    public function forget(): void
    {
        $this->installed = false;
    }

    public function markerPath(): string
    {
        return (string) config('installer.marker_path');
    }
}
