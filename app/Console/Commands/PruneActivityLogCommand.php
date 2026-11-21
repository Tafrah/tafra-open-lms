<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

/**
 * تنظيف سجل النشاط مع **الحفاظ على السجلات الدائمة**.
 *
 * ليه أمر بتاعنا بدل `activitylog:clean` بتاع الباكدج؟
 * أمر الباكدج بيقرا `clean_after_days` بس وبيمسح **كل** حاجة أقدم منها —
 * بما فيها `security` و`financial`. و`docs/11` بند ٤ و`docs/20` بند ١٣
 * الاتنين بيقولوا إن دول بيتحفظوا للأبد.
 *
 * الكونفيج كان فيه `retention_months` و`permanent_log_names` مكتوبين
 * من الأول — بس مفيش كود كان بيقراهم، فالجدولة كانت بتمسح سجل التدقيق
 * الأمني بعد سنة بالظبط زي أي حاجة تانية.
 */
final class PruneActivityLogCommand extends Command
{
    protected $signature = 'activitylog:prune
                            {--months= : عدد الشهور بدل الإعداد}
                            {--dry : عرض العدد من غير حذف}';

    protected $description = 'حذف سجلات النشاط الأقدم من مدة الاحتفاظ، ما عدا السجلات الدائمة';

    public function handle(): int
    {
        $months = (int) ($this->option('months') ?? config('activitylog.retention_months', 12));
        $permanent = (array) config('activitylog.permanent_log_names', []);

        $query = Activity::query()
            ->where('created_at', '<', now()->subMonths($months));

        if ($permanent !== []) {
            $query->where(function ($builder) use ($permanent): void {
                $builder->whereNull('log_name')->orWhereNotIn('log_name', $permanent);
            });
        }

        if ($this->option('dry')) {
            $this->table(
                ['المدة', 'محمي', 'هيتحذف'],
                [["{$months} شهر", implode('، ', $permanent) ?: '—', (string) $query->count()]],
            );

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("اتحذف {$deleted} سجل أقدم من {$months} شهر.");

        if ($permanent !== []) {
            $this->line('  المحفوظ دايماً: '.implode('، ', $permanent));
        }

        return self::SUCCESS;
    }
}
