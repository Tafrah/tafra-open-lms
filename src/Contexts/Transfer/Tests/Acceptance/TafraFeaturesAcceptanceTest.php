<?php

declare(strict_types=1);

use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Transfer\Presentation\Filament\Pages\TafraFeatures;

it('المدير بيفتح «مميزات طفرة» وزرار التواصل بيروح لواتساب برسالة جاهزة', function (): void {
    config(['transfer.tafra.whatsapp' => '+20 100 000 0000']);
    actingAsRole('admin');

    $this->get(TafraFeatures::getUrl())
        ->assertOk()
        ->assertSee(__('transfer::transfer.tafra.features.payments.title'))
        ->assertSee('https://wa.me/201000000000?text=', false);
});

it('من غير رقم واتساب الزرار بيفتح موقع طفرة', function (): void {
    config(['transfer.tafra.whatsapp' => '', 'transfer.tafra.url' => 'https://tafra-tech.com']);

    expect(TafraFeatures::contactUrl('payments'))->toBe('https://tafra-tech.com');
});

it('المساعد مالوش الصفحة، وLMS_SHOW_TAFRA=false بيخفيها', function (): void {
    actingAsRole('assistant');
    $this->get(TafraFeatures::getUrl())->assertForbidden();

    config(['transfer.tafra.show_features' => false]);
    actingAsRole('admin');
    $this->get(TafraFeatures::getUrl())->assertForbidden();
});

it('رسالة «منصتك كبرت» بتظهر بس بعد الرقم', function (): void {
    config(['transfer.tafra.nudge.students' => 3]);
    actingAsRole('admin');
    $home = '/admin/t/'.currentTenant()->slug;
    $text = __('transfer::transfer.tafra.nudge.action');

    Student::factory()->count(2)->create();
    $this->get($home)->assertOk()->assertDontSee($text);

    cache()->flush();
    Student::factory()->create();
    $this->get($home)->assertOk()->assertSee($text);
});
