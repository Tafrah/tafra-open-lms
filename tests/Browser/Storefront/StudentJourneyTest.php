<?php

declare(strict_types=1);

require_once __DIR__.'/../../../src/Contexts/Storefront/Tests/storefront-helpers.php';

use Src\Contexts\Billing\Domain\Enums\RechargeCardType;
use Src\Contexts\Billing\Domain\Models\Coupon;
use Src\Contexts\Billing\Domain\Models\RechargeCard;
use Src\Contexts\Billing\Domain\Models\RechargeCardGroup;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Students\Infrastructure\Notifications\InvoiceReviewedNotification;

/*
|--------------------------------------------------------------------------
| رحلة الطالب على الموقع في متصفح حقيقي
|--------------------------------------------------------------------------
| اختبارات Livewire العادية مابتعدّيش على الـ persistent middleware بتاع
| `/livewire/update` — الطريق ده بس بيتشاف في متصفح حقيقي. تحديث Livewire
| (4.4.9) كسر كل مكوّنات الموقع بـ 404 والاختبارات العادية كانت خضرا.
*/

beforeEach(fn () => inTenant());

function browserStudentLogin(): object
{
    $student = sfStudent(['phone' => '01012345678']);

    $page = visit(route('site.login', absolute: false));
    $page->fill('#phone', '01012345678')
        ->fill('#password', 'password')
        ->click('form:has(#password) button[type="submit"]')
        ->assertPathIs(route('site.dashboard', absolute: false));

    return $page;
}

it('الطالب بيدخل ويشحن كود من المحفظة والرصيد بيتحدّث من غير ما يطلع برا', function (): void {
    $group = RechargeCardGroup::factory()->create(['type' => RechargeCardType::Wallet]);
    $card = RechargeCard::factory()->create([
        'recharge_card_group_id' => $group->getKey(),
        'code' => '123456789012',
        'amount' => 40,
    ]);

    browserStudentLogin();

    $page = visit(route('site.wallet', absolute: false));
    $page->fill('input[wire\\:model="code"]', $card->code)
        ->click('form[wire\\:submit="redeem"] button[type="submit"]')
        ->assertSee(__('storefront::storefront.wallet.redeem_another'));

    expect($card->fresh()?->used_at)->not->toBeNull();

    // الجلسة لسه شغالة بعد طلب Livewire
    visit(route('site.wallet', absolute: false))->assertPathIs(route('site.wallet', absolute: false));

    assertUiQuality($page);
});

it('الطالب بيطبّق كوبون ويشتري كورس من المحفظة ويفتح الدرس', function (): void {
    $course = sfCourse(['price' => 100]);
    Coupon::factory()->create(['code' => 'SAVE20', 'value' => 20]);

    browserStudentLogin();
    Student::query()->where('phone', '01012345678')->update(['balance' => 100]);

    $page = visit(route('site.courses.show', $course->slug, absolute: false));
    $page->fill('#coupon-'.$course->getKey(), 'SAVE20')
        ->click('form[wire\\:submit="applyCoupon"] button[type="submit"]')
        ->assertSee('SAVE20')
        ->click('form[action$="/wallet"] button[type="submit"]')
        ->assertPathBeginsWith(route('site.learn.course', $course->slug, absolute: false));

    $student = Student::query()->where('phone', '01012345678')->firstOrFail();

    expect((float) $student->balance)->toBe(20.0);

    assertUiQuality($page);
});

it('الطالب بيشوف جرس الإشعارات وبيفتح الصفحة على موبايل', function (): void {
    browserStudentLogin();
    $student = Student::query()->where('phone', '01012345678')->firstOrFail();
    $student->notifyNow(new InvoiceReviewedNotification(1, true, 'كيمياء', null, null));

    $page = visit(route('site.dashboard', absolute: false))->resize(390, 844);
    $page->assertVisible('[data-testid="notifications-bell"]')
        ->click('[data-testid="notifications-bell"]')
        ->assertPathIs(route('site.notifications', absolute: false))
        ->assertSee('كيمياء');

    expect($student->fresh()?->unreadNotifications()->count())->toBe(0);

    assertUiQuality($page);
});
