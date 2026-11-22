<?php

declare(strict_types=1);

require_once __DIR__.'/../storefront-helpers.php';

use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Students\Infrastructure\Notifications\AttemptGradedNotification;
use Src\Contexts\Students\Infrastructure\Notifications\InvoiceReviewedNotification;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/*
| الجرس وصفحة «الإشعارات» في منطقة الطالب + إعلانات المدرس.
*/

beforeEach(fn () => inTenant());

function sfNotify(Student $student, bool $approved = false, string $course = 'Physics 1'): void
{
    $student->notifyNow(new InvoiceReviewedNotification(1, $approved, $course, $approved ? null : 'صورة مش واضحة', null));
}

it('الجرس بيعرض عدد الإشعارات اللي ماتقرتش', function (): void {
    $student = sfLogin();
    sfNotify($student);
    sfNotify($student);

    $this->get(route('site.dashboard'))
        ->assertOk()
        ->assertSee('data-testid="notifications-bell"', false)
        ->assertSee(__('storefront::storefront.notifications.bell_unread', ['count' => fc_number(2)]));
});

it('فتح صفحة الإشعارات بيعرضها بلغة الطالب ويعلّمها مقروءة', function (): void {
    $student = sfLogin();
    sfNotify($student, approved: false, course: 'Physics 1');
    $student->notifyNow(new AttemptGradedNotification(5, 'Unit quiz', 87.5));

    $this->get(route('site.notifications'))
        ->assertOk()
        ->assertSee(__('storefront::storefront.notifications.kinds.invoice_rejected.title'))
        ->assertSee(__('storefront::storefront.notifications.kinds.invoice_rejected.body', ['course' => 'Physics 1']))
        ->assertSee('صورة مش واضحة')
        ->assertSee(__('storefront::storefront.notifications.kinds.attempt_graded.body', ['exam' => 'Unit quiz', 'percentage' => fc_number(87.5, 1)]))
        ->assertSee(route('site.attempts.result', 5), false)
        ->assertSee(__('storefront::storefront.notifications.new'));

    expect($student->unreadNotifications()->count())->toBe(0);

    // الفتحة التانية: مفيش «جديد» والجرس صفر
    $this->get(route('site.notifications'))
        ->assertOk()
        ->assertDontSee(__('storefront::storefront.notifications.bell_unread', ['count' => fc_number(1)]));
});

it('الطالب بيشوف إشعاراته هو بس', function (): void {
    $other = sfStudent();
    sfNotify($other, course: 'Secret course');
    sfLogin();

    $this->get(route('site.notifications'))
        ->assertOk()
        ->assertDontSee('Secret course')
        ->assertSee(__('storefront::storefront.empty.notifications_title'));
});

it('الصفحة محتاجة دخول', function (): void {
    $this->get(route('site.notifications'))->assertRedirect(route('site.login'));
});

it('إعلانات المدرس المنشورة بتظهر للطلبة (الأحدث الأول) — المسودة والمنتهية لأ', function (): void {
    Announcement::factory()->published()->create(['title' => ['ar' => 'Old news', 'en' => 'Old news'], 'published_at' => now()->subDays(3)]);
    Announcement::factory()->published()->create(['title' => ['ar' => 'Fresh news', 'en' => 'Fresh news'], 'published_at' => now()->subHour()]);
    Announcement::factory()->draft()->create(['title' => ['ar' => 'Draft news', 'en' => 'Draft news']]);
    Announcement::factory()->published()->expired()->create(['title' => ['ar' => 'Expired news', 'en' => 'Expired news']]);
    sfLogin();

    $this->get(route('site.notifications'))
        ->assertOk()
        ->assertSeeInOrder(['Fresh news', 'Old news'])
        ->assertDontSee('Draft news')
        ->assertDontSee('Expired news');

    $this->get(route('site.dashboard'))
        ->assertOk()
        ->assertSee(__('storefront::storefront.notifications.announcements'))
        ->assertSee('Fresh news');
});

it('إعلانات مؤسسة تانية مابتظهرش', function (): void {
    $other = Tenant::factory()->create();
    app(TenantContext::class)->forTenant($other->getKey(), fn () => Announcement::factory()->published()->create(['title' => ['ar' => 'Foreign news', 'en' => 'Foreign news']]));
    sfLogin();

    $this->get(route('site.notifications'))->assertOk()->assertDontSee('Foreign news');
});
