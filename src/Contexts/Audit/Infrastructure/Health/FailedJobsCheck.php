<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Infrastructure\Health;

use Illuminate\Support\Facades\DB;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

/**
 * تراكم المهام الفاشلة (docs/13 بند ٥).
 *
 * `QueueCheck` بيقول إن الطابور **بيشتغل** — مش إن الشغل بينجح.
 * طابور صحّي بيبلع مهام فاشلة بالساعات ومحدش واخد باله، فالفحص ده
 * بيبص على `failed_jobs` نفسه.
 *
 * الحدود من الكونفيج مش مكتوبة في الكود — الرقم المناسب بيختلف
 * من مشروع للتاني.
 */
final class FailedJobsCheck extends Check
{
    public function run(): Result
    {
        $table = (string) config('queue.failed.table', 'failed_jobs');

        // نفس الاتصال اللي Laravel بيكتب عليه المهام الفاشلة — مش الافتراضي،
        // واللي في وضع multi ممكن يبقى قاعدة مؤسسة (docs/03 بند ٣)
        $count = DB::connection((string) config('queue.failed.database'))->table($table)->count();

        $warn = (int) config('health.failed_jobs.warn', 25);
        $fail = (int) config('health.failed_jobs.fail', 50);

        $result = Result::make()
            ->meta(['count' => $count, 'warn' => $warn, 'fail' => $fail])
            ->shortSummary((string) $count);

        if ($count >= $fail) {
            return $result->failed(__('audit::audit.health.failed_jobs.failed', ['count' => $count]));
        }

        if ($count >= $warn) {
            return $result->warning(__('audit::audit.health.failed_jobs.warning', ['count' => $count]));
        }

        return $result->ok(__('audit::audit.health.failed_jobs.ok', ['count' => $count]));
    }
}
