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
// Horizon محتاج Redis — على الاستضافة المشتركة الطابور على القاعدة ومفيش Horizon
Schedule::command('horizon:snapshot')->everyFiveMinutes()->onOneServer()
    ->when(static fn (): bool => config('queue.default') === 'redis');

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

// ═══════════ عامل الطابور من غير supervisor (الاستضافة المشتركة) ═══════════
// مفيش عامل دايم: كل دقيقة الكرون بيشغّل عامل بيفضّي الطابور ويقفل قبل
// الدقيقة الجاية (`--max-time` أقل من ٦٠). `withoutOverlapping` عشان لو
// دورة طوّلت ماتتفتحش اتنين. على VPS فيه Horizon/supervisor:
// QUEUE_WORKER_VIA_SCHEDULER=false (config/installer.php).
Schedule::command(sprintf(
    'queue:work --queue=%s --stop-when-empty --max-time=%d --tries=%d',
    (string) config('installer.scheduled_worker.queues'),
    (int) config('installer.scheduled_worker.max_time'),
    (int) config('installer.scheduled_worker.tries'),
))
    ->everyMinute()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->runInBackground()
    ->when(static fn (): bool => (bool) config('installer.scheduled_worker.enabled')
        && ! in_array(config('queue.default'), ['sync', 'redis'], true));

// ═══════════ الامتحانات: تسليم المحاولات اللي وقتها خلص والطالب قفل الصفحة ═══════════
Schedule::command('exams:finalize-expired')->everyFiveMinutes()->onOneServer()->withoutOverlapping();

// فحص إصدار جديد على GitHub (طلب GET واحد، مفيش بيانات بتتبعت) — LMS_UPDATE_CHECK=false بيقفله
if ((bool) config('installer.updates.enabled')) {
    Schedule::command('lms:check-update')->dailyAt('04:10')->onOneServer();
}

// ═══════════ نسخة التجربة: ترجع لأصلها كل يوم (DEMO_MODE + DEMO_AUTO_RESET) ═══════════
// lms:demo نفسه بيرفض يلمس منصة متثبّتة بجد — الشرط هنا عشان مايشتغلش أصلاً
Schedule::command('lms:demo --fresh')->dailyAt((string) config('demo.reset_at', '04:00'))
    ->onOneServer()->withoutOverlapping()
    ->when(static fn (): bool => (bool) config('demo.enabled') && (bool) config('demo.auto_reset'));
