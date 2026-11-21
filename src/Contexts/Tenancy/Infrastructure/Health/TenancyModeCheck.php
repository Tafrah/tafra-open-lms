<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Infrastructure\Health;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Src\Support\Infrastructure\Tenancy\TenancyMode;
use Src\Support\Infrastructure\Tenancy\TenancyModeLock;

/**
 * الوضع المقفول = الوضع في `.env`؟ (docs/03 بند ١)
 *
 * التطبيق نفسه بيرفض يقوم لو الوضعين مختلفين — الفحص ده بيمسك الحالة
 * اللي قبلها: تثبيت اتنشر من غير `tenancy:init`، فالوضع مش مقفول وأي
 * تغيير في `.env` هيعدّي بصمت.
 */
final class TenancyModeCheck extends Check
{
    public function run(): Result
    {
        $configured = TenancyMode::current();
        $locked = app(TenancyModeLock::class)->stored();

        $result = Result::make()
            ->meta(['configured' => $configured, 'locked' => $locked])
            ->shortSummary($configured);

        if ($locked === null) {
            return $result->warning(__('tenancy::tenancy.health.mode.unlocked', ['mode' => $configured]));
        }

        if ($locked !== $configured) {
            return $result->failed(__('tenancy::tenancy.mode.mismatch', ['configured' => $configured, 'locked' => $locked]));
        }

        return $result->ok(__('tenancy::tenancy.health.mode.ok', ['mode' => $configured]));
    }
}
