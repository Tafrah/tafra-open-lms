<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Notifications\Application\Jobs\GenerateWeeklyDigestJob;
use Src\Contexts\Notifications\Domain\Models\NotificationPreference;
use Src\Contexts\Notifications\Infrastructure\Notifications\WeeklyDigestNotification;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Queue\RestoresLogContext;

/**
 * شرط قبول `docs/13`: «اختبار Pest: Job بيشتغل بالمستأجر الصح».
 *
 * العامل مش شايف الطلب — يعني `TenantContext` عنده فاضي وقت التنفيذ.
 * لو الـ Job ماحملش المؤسسة معاه ومارجّعهاش، إما بيرمي
 * `MissingTenantContextException`، أو — الأسوأ — بيشتغل على المؤسسة
 * اللي صادف إنها متسيبة في السياق من job قبله على نفس العامل.
 */
it('الـ Job بياخد المؤسسة من وقت الإرسال مش وقت التنفيذ', function (): void {
    $a = Tenant::factory()->create();
    $b = Tenant::factory()->create();

    $context = app(TenantContext::class);

    $context->set($a->getKey());
    $job = new GenerateWeeklyDigestJob($b->getKey());

    // المؤسسة المستهدفة صريحة في الكونستركتور — مش مسحوبة من السياق الحالي
    expect($job->tenantId)->toBe($b->getKey())
        ->and($job->digestTenantId)->toBe($b->getKey());
});

it('الميدلوير بيرجّع سياق المؤسسة قبل تنفيذ الـ Job', function (): void {
    $tenant = Tenant::factory()->create();

    $context = app(TenantContext::class);
    $job = new GenerateWeeklyDigestJob($tenant->getKey());

    // زي العامل بالظبط: مفيش سياق
    $context->set(null);

    $seen = null;
    (new RestoresLogContext)->handle($job, function () use ($context, &$seen): void {
        $seen = $context->id();
    });

    expect($seen)->toBe($tenant->getKey());
});

it('الـ Job بيبلّغ مستخدمي مؤسسته هي بس', function (): void {
    Notification::fake();

    $a = Tenant::factory()->create();
    $b = Tenant::factory()->create();

    $context = app(TenantContext::class);

    // الملخص الأسبوعي **مقفول افتراضياً** في الكتالوج، فالاتنين بيفعّلوه.
    // من غير كده الاختبار هيعدّي لأن مفيش حد بيستلم أصلاً — مش لأن العزل شغّال.
    $optIn = static fn (User $user, Tenant $tenant): NotificationPreference => NotificationPreference::query()->create([
        'user_id' => $user->getKey(),
        'tenant_id' => $tenant->getKey(),
        'notification_key' => WeeklyDigestNotification::key(),
        'channels' => ['mail'],
        'enabled' => true,
    ]);

    $context->set($a->getKey());
    $insider = User::factory()->create();
    $insider->tenants()->attach($a->getKey(), ['joined_at' => now()]);
    $optIn($insider, $a);

    $context->set($b->getKey());
    $outsider = User::factory()->create();
    $outsider->tenants()->attach($b->getKey(), ['joined_at' => now()]);
    $optIn($outsider, $b);

    // العامل بيبدأ من غير سياق
    $context->set(null);

    $job = new GenerateWeeklyDigestJob($a->getKey());
    (new RestoresLogContext)->handle($job, fn () => app()->call([$job, 'handle']));

    Notification::assertSentTo($insider, WeeklyDigestNotification::class);
    Notification::assertNotSentTo($outsider, WeeklyDigestNotification::class);
});
