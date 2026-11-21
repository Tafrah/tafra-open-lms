<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Src\Contexts\Settings\Domain\Settings\NotificationSettings;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

final class PruneNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune {--days= : عدد الأيام بدل الإعداد المحفوظ}';

    protected $description = 'حذف الإشعارات المقروءة الأقدم من المدة المحددة';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? $this->configuredDays());

        // الإشعارات مركزية (ملك المستخدم) — في الوضعين
        $deleted = DB::connection(TenancyMode::centralConnection())->table('notifications')
            ->whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays($days))
            ->delete();

        $this->info("اتحذف {$deleted} إشعار مقروء أقدم من {$days} يوم.");

        return self::SUCCESS;
    }

    private function configuredDays(): int
    {
        try {
            return app(NotificationSettings::class)->prune_read_after_days;
        } catch (\Throwable) {
            return 90;
        }
    }
}
