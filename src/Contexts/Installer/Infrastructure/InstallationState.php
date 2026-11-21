<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Infrastructure;

use Src\Support\Infrastructure\Tenancy\TenancyModeLock;

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

        return false;
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
