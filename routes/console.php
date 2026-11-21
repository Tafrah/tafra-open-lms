<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;
use Src\Contexts\Notifications\Application\Jobs\GenerateWeeklyDigestJob;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/*
|--------------------------------------------------------------------------
| المهام المجدولة
|--------------------------------------------------------------------------
| قواعد ثابتة (docs/13 بند ٣):
|  · `onOneServer()` على كل مهمة — عشان ماتتنفّذش مرتين
|  · `withoutOverlapping()` لأي مهمة طويلة
|  · `name()` لأي `Schedule::call()`
|  · مفيش منطق هنا — الجدول بيستدعي Command أو يـ dispatch Job
*/

// ═══════════ النسخ الاحتياطي ═══════════
Schedule::command('backup:clean')->dailyAt('01:00')->onOneServer();
Schedule::command('backup:run')->dailyAt('01:30')->onOneServer()->runInBackground();
Schedule::command('backup:monitor')->dailyAt('02:00')->onOneServer();

// ═══════════ التنظيف ═══════════
Schedule::command('notifications:prune')->dailyAt('03:00')->onOneServer();
// أمرنا مش `activitylog:clean`: ده بيمسح كل حاجة أقدم من المدة،
// وسجلات `security` و`financial` بتتحفظ للأبد (docs/20 بند ١٣).
Schedule::command('activitylog:prune')->weeklyOn(1, '03:30')->onOneServer();
Schedule::command('queue:prune-failed --hours=168')->weekly()->onOneServer();
Schedule::command('horizon:snapshot')->everyFiveMinutes()->onOneServer();

// ═══════════ الصحة ═══════════
Schedule::command('health:check')->everyFifteenMinutes()->onOneServer();

// ═══════════ الملخص الأسبوعي — Job لكل مؤسسة ═══════════
Schedule::call(function (TenantContext $context): void {
    $context->forEachTenant(static function (Tenant $tenant): void {
        GenerateWeeklyDigestJob::dispatch((int) $tenant->getKey());
    });
    // ⚠️ `name()` لازم تيجي **قبل** `onOneServer()` على Schedule::call —
    // غير كده Laravel بيرمي LogicException (docs/13 بند ٣).
})->name('weekly-digests')->weeklyOn(0, '08:00')->onOneServer();

// ═══════════ الإصدار المجدول للإعلانات ═══════════
Schedule::command('announcements:publish-scheduled')->everyFiveMinutes()->onOneServer()->withoutOverlapping();
