<?php

declare(strict_types=1);

use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource;
use Src\Contexts\Academy\Presentation\Filament\Resources\EducationalLevelResource;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\MoneyTransactionResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentMethodResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource;
use Src\Contexts\Transfer\Presentation\Filament\Pages\DataTransfer;

/*
|--------------------------------------------------------------------------
| رحلات شاشات المنصة التعليمية (docs/17 بند ١٣)
|--------------------------------------------------------------------------
| [الشاشة، مفتاح الجولة، مكان الرابط]
*/

dataset('lms_screens', [
    'level' => [EducationalLevelResource::class, 'academy.level', 'sidebar'],
    'lesson' => [OnlineLessonResource::class, 'academy.lesson', 'sidebar'],
    'bootcamp' => [BootcampResource::class, 'academy.bootcamp', 'sidebar'],
    'exam' => [OnlineExamResource::class, 'exams.exam', 'sidebar'],
    'attempt' => [OnlinePassedExamResource::class, 'exams.attempt', 'sidebar'],
    'student' => [StudentResource::class, 'students.student', 'sidebar'],
    'invoice' => [PaymentInvoiceResource::class, 'billing.invoice', 'sidebar'],
    'method' => [PaymentMethodResource::class, 'billing.method', 'sidebar'],
    'card_group' => [RechargeCardGroupResource::class, 'billing.card_group', 'sidebar'],
    'coupon' => [CouponResource::class, 'billing.coupon', 'sidebar'],
    'transaction' => [MoneyTransactionResource::class, 'billing.transaction', 'sidebar'],
    'enrollment' => [EnrollmentResource::class, 'billing.enrollment', 'sidebar'],
    'data transfer' => [DataTransfer::class, 'transfer.page', 'sidebar'],
]);

it('المدير بيوصل لشاشة المنصة والجولة بتبدأ وبتتقفل', function (string $screen, string $tour, string $area): void {
    actingAsRole('admin');

    journeyToScreen(adminHome(), navPathFor($screen), $tour, $area);
})->with('lms_screens');

it('شاشات المنصة سليمة على موبايل بالوضع الداكن وبالإنجليزي', function (string $screen): void {
    $user = actingAsRole('admin');
    $user->forceFill(['locale' => 'en'])->save();

    assertMobileDarkQuality(screenPath($screen), 'en');
})->with('lms_screens');

/*
| شاشات من غير رابط في السايدبار — بتتفتح من جوه شاشة تانية (أجزاء الكورس):
| BootcampSectionResource
| رحلتها جوه رحلة الكورس — الاختبار ده بيتأكد إنها متسجلة.
*/
it('الشاشات الداخلية متسجلة في اللوحة', function (): void {
    actingAsRole('admin');

    foreach ([BootcampSectionResource::class] as $screen) {
        expect(screenPath($screen))->toStartWith('/admin');
    }
});
