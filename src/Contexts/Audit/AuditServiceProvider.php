<?php

declare(strict_types=1);

namespace Src\Contexts\Audit;

use Spatie\Health\Checks\Checks\BackupsCheck;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;
use Src\Contexts\Audit\Infrastructure\Health\FailedJobsCheck;
use Src\Support\Infrastructure\Providers\ContextServiceProvider;

final class AuditServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'audit';
    }

    protected function path(): string
    {
        return __DIR__;
    }

    protected function bootContext(): void
    {
        $this->registerHealthChecks();
    }

    /**
     * فحوصات صحة النظام (docs/11 بند ٧).
     *
     * DebugModeCheck و OptimizedAppCheck مقصودين: هما اللي بيمسكوا
     * أشهر غلطتين في الإنتاج (APP_DEBUG=true وكاش مش مبني).
     */
    /** أول قرص نسخ مضبوط — نفس اللي `backup:run` بيكتب عليه. */
    private function backupDisk(): string
    {
        $disks = (array) config('backup.backup.destination.disks', []);

        return (string) (reset($disks) ?: 'backups-local');
    }

    private function registerHealthChecks(): void
    {
        if ($this->app->runningUnitTests()) {
            return;
        }

        Health::checks([
            DatabaseCheck::new(),
            CacheCheck::new(),
            RedisCheck::new(),
            QueueCheck::new()->onQueue(['default', 'notifications']),
            HorizonCheck::new(),
            ScheduleCheck::new(),
            DatabaseConnectionCountCheck::new()->warnWhenMoreConnectionsThan(50),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(85),
            OptimizedAppCheck::new(),
            DebugModeCheck::new(),
            EnvironmentCheck::new(),

            // الطابور شغّال ≠ الشغل بينجح — ده بيبص على failed_jobs (docs/13 بند ٥)
            FailedJobsCheck::new(),

            // «نسخة احتياطية مش مُختبَرة = مفيش نسخة احتياطية» (docs/11 بند ٩).
            // الفحص ده هو اللي بيقول إن النسخ شغّالة فعلاً — من غيره
            // الجدولة ممكن تكون واقفة من شهر ومحدش واخد باله.
            BackupsCheck::new()
                ->onDisk($this->backupDisk())
                ->youngestBackShouldHaveBeenMadeBefore(now()->subDay()),
        ]);
    }
}
