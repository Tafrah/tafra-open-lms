<?php

declare(strict_types=1);

namespace Src\Contexts\Demo\Application;

use Src\Contexts\Installer\Infrastructure\InstallationState;

/**
 * هل المنصة نسخة تجربة؟ شرطين مع بعض (config/demo.php):
 *
 *  1. `DEMO_MODE=true` في البيئة
 *  2. التثبيت معمول بـ `lms:demo` — علامة `demo: true` في installed.json
 *
 * الشرط التاني هو الأمان الحقيقي: أكاديمية حقيقية حد فعّل فيها المتغيّر
 * بالغلط مابيظهرش فيها دخول بضغطة.
 */
final readonly class DemoMode
{
    public function __construct(private InstallationState $state) {}

    public function enabled(): bool
    {
        return (bool) config('demo.enabled');
    }

    public function active(): bool
    {
        return $this->enabled() && $this->installedAsDemo();
    }

    public function installedAsDemo(): bool
    {
        return ($this->state->marker()['demo'] ?? false) === true;
    }

    public function markInstalledAsDemo(): void
    {
        $this->state->markInstalled([...($this->state->marker() ?? []), 'demo' => true]);
    }

    /** @return array<string, array<string, string>> */
    public function accounts(?string $guard = null): array
    {
        /** @var array<string, array<string, string>> $accounts */
        $accounts = (array) config('demo.accounts', []);

        return $guard === null
            ? $accounts
            : array_filter($accounts, static fn (array $account): bool => ($account['guard'] ?? '') === $guard);
    }

    /** @return array<string, string>|null */
    public function account(string $key): ?array
    {
        return $this->accounts()[$key] ?? null;
    }

    public function password(): string
    {
        return (string) config('demo.password');
    }
}
