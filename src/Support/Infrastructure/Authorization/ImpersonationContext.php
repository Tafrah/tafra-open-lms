<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Authorization;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use STS\FilamentImpersonate\Facades\Impersonation;

/**
 * غلاف رفيع حوالين باكدج الانتحال — عشان الـ Policies والـ Actions
 * ما تعتمدش على الباكدج مباشرة، ولو غيّرناه نغيّر مكان واحد.
 */
final class ImpersonationContext
{
    private const STARTED_AT_KEY = 'fc.impersonation_started_at';

    /** نفس المفتاح اللي مسار «إنهاء الانتحال» في الباكدج بيقرا منه */
    private const BACK_TO_KEY = 'impersonate.back_to';

    public function isActive(): bool
    {
        return Impersonation::isImpersonating();
    }

    public function impersonatorId(): int|string|null
    {
        return Impersonation::getImpersonatorId();
    }

    /**
     * @param string|null $backTo المكان اللي المنتحِل بيرجع له لما ينهي الجلسة
     */
    public function enter(Authenticatable $impersonator, Authenticatable $target, ?string $backTo = null): bool
    {
        if ($backTo !== null) {
            session()->put(self::BACK_TO_KEY, $backTo);
        }

        $entered = Impersonation::enter($impersonator, $target);

        if ($entered) {
            $this->markStarted();
        } else {
            session()->forget(self::BACK_TO_KEY);
        }

        return $entered;
    }

    /** بيسحب مكان الرجوع (مرة واحدة) — `null` لو الجلسة اتبدأت من غير واحد. */
    public function pullBackTo(): ?string
    {
        $value = session()->pull(self::BACK_TO_KEY);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function markStarted(): void
    {
        session()->put(self::STARTED_AT_KEY, now()->toIso8601String());
    }

    public function startedAt(): ?CarbonImmutable
    {
        $value = session()->get(self::STARTED_AT_KEY);

        return is_string($value) ? CarbonImmutable::parse($value) : null;
    }

    public function hasExpired(int $maxMinutes): bool
    {
        $startedAt = $this->startedAt();

        return $startedAt !== null && $startedAt->addMinutes($maxMinutes)->isPast();
    }

    public function forget(): void
    {
        session()->forget(self::STARTED_AT_KEY);
    }
}
