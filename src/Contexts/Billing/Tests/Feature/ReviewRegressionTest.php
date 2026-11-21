<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Contexts\Academy\Application\Actions\RecordItemViewAction;
use Src\Contexts\Academy\Application\Queries\ItemAccess;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Billing\Application\Actions\CheckoutAction;
use Src\Contexts\Billing\Application\Actions\EnrollStudentAction;
use Src\Contexts\Billing\Application\Actions\GenerateRechargeCardsAction;
use Src\Contexts\Billing\Application\Actions\ReviewInvoiceAction;
use Src\Contexts\Billing\Application\Actions\WalletAction;
use Src\Contexts\Billing\Application\DataObjects\RechargeCardBatchData;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Enums\RechargeCardType;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\Coupon;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\RechargeCard;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource\Pages\ViewRechargeCardGroup;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource\RelationManagers\CardsRelationManager;
use Src\Contexts\Exams\Application\Actions\GradeEssayAnswerAction;
use Src\Contexts\Exams\Application\Actions\SaveAnswerAction;
use Src\Contexts\Exams\Application\Actions\StartAttemptAction;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Students\Domain\Models\Student;

/*
| مراجعة الأمان (نوفمبر ٢٠٢٦): كل اختبار هنا كان بيثبت ثغرة حقيقية — دلوقتي بيثبت إنها اتقفلت.
*/

beforeEach(function (): void {
    inTenant();
    Storage::fake('local');
    Storage::fake('public');
});

it('المساعد والمشاهد مايشوفوش أكواد الشحن الخام', function (string $role): void {
    $admin = userWithRole('admin');
    $group = app(GenerateRechargeCardsAction::class)->handle(new RechargeCardBatchData('g', RechargeCardType::Wallet, 3, 500.0), $admin);
    $code = RechargeCard::query()->first()->code;

    actingAsRole($role);

    Livewire::test(CardsRelationManager::class, ['ownerRecord' => $group, 'pageClass' => ViewRechargeCardGroup::class])
        ->assertOk()->loadTable()->assertDontSee($code)->assertSee(substr($code, -4));
})->with(['assistant', 'viewer']);

it('المدير بيشوف الكود كامل عشان يطبعه', function (): void {
    $admin = actingAsRole('admin');
    $group = app(GenerateRechargeCardsAction::class)->handle(new RechargeCardBatchData('g', RechargeCardType::Wallet, 1, 50.0), $admin);

    Livewire::test(CardsRelationManager::class, ['ownerRecord' => $group, 'pageClass' => ViewRechargeCardGroup::class])
        ->loadTable()->assertSee(RechargeCard::query()->first()->code);
});

it('المشاهد مايقدرش يوقف كارت', function (): void {
    $admin = userWithRole('admin');
    $group = app(GenerateRechargeCardsAction::class)->handle(new RechargeCardBatchData('g', RechargeCardType::Wallet, 1, 500.0), $admin);
    $card = RechargeCard::query()->first();

    actingAsRole('viewer');

    try {
        Livewire::test(CardsRelationManager::class, ['ownerRecord' => $group, 'pageClass' => ViewRechargeCardGroup::class])
            ->call('updateTableColumnState', 'is_active', (string) $card->getKey(), false);
    } catch (Throwable) {
        // مرفوض — المهم إن الكارت مااتغيّرش
    }

    expect($card->fresh()->is_active)->toBeTrue();
});

it('كوبون «لأول واحد بس» مايتحجزش لأكتر من طالب بالتحويلات المعلّقة', function (): void {
    $bootcamp = Bootcamp::factory()->create(['price' => 200]);
    Coupon::factory()->create(['code' => 'ONE', 'max_redemptions' => 1, 'max_per_user' => 1]);

    app(CheckoutAction::class)->handle(Student::factory()->create(), $bootcamp, PaymentGateway::ManualTransfer, 'ONE');

    expect(fn () => app(CheckoutAction::class)->handle(Student::factory()->create(), $bootcamp, PaymentGateway::ManualTransfer, 'ONE'))
        ->toThrow(BillingException::class);
});

it('الشراء بالمحفظة بيلغي التحويل المعلّق، والقبول بعدها مرفوض (مافيش دفع مرتين)', function (): void {
    $admin = userWithRole('admin');
    $student = Student::factory()->create();
    $bootcamp = Bootcamp::factory()->create(['price' => 100]);
    $pending = app(CheckoutAction::class)->handle($student, $bootcamp, PaymentGateway::ManualTransfer);
    app(WalletAction::class)->deposit($student, 100, TransactionSource::Admin);
    app(CheckoutAction::class)->handle($student, $bootcamp, PaymentGateway::Wallet);

    expect($pending->fresh()->status)->toBe(InvoiceStatus::Canceled)
        ->and(fn () => app(ReviewInvoiceAction::class)->approve($pending->fresh(), $admin))->toThrow(Exception::class)
        ->and(PaymentInvoice::query()->where('status', InvoiceStatus::Paid)->count())->toBe(1);
});

it('ريفرش آخر مشاهدة مسموح بيها مابيقفلش الدرس', function (): void {
    $student = Student::factory()->create();
    $item = BootcampSectionItem::factory()->create(['allowed_views_number' => 1]);
    app(EnrollStudentAction::class)->handle($student, $item->section->bootcamp, EnrollmentSource::Admin);

    expect(app(ItemAccess::class)->denial($student, $item))->toBeNull();
    app(RecordItemViewAction::class)->handle($student, $item);
    expect(app(ItemAccess::class)->denial($student, $item->fresh()))->toBeNull();

    $this->travel(2)->hours();
    expect(app(ItemAccess::class)->denial($student, $item->fresh()))->toBe('views_exhausted');
});

it('مينفعش تصحيح مقالي في محاولة لسه مفتوحة', function (): void {
    $admin = userWithRole('admin');
    $student = Student::factory()->create();
    $exam = OnlineExam::factory()->create();
    $q = Question::factory()->essay()->create(['online_exam_id' => $exam->getKey(), 'mark' => 5]);
    $attempt = app(StartAttemptAction::class)->handle($student, $exam);
    $answer = app(SaveAnswerAction::class)->handle($student, $attempt, $q->getKey(), text: 'abc');

    expect(fn () => app(GradeEssayAnswerAction::class)->handle($answer, 5, null, $admin))
        ->toThrow(AuthorizationException::class);
});

it('محاولة وقتها خلص بتتسلّم حتى لو الطالب خلّص محاولاته', function (): void {
    $student = Student::factory()->create();
    $exam = OnlineExam::factory()->create(['allowed_chances' => 1, 'duration_in_seconds' => 60]);
    $q = Question::factory()->withChoices()->create(['online_exam_id' => $exam->getKey(), 'mark' => 5]);
    $attempt = app(StartAttemptAction::class)->handle($student, $exam);
    app(SaveAnswerAction::class)->handle($student, $attempt, $q->getKey(), (int) $q->choices()->where('is_correct', true)->value('id'));

    $this->travel(10)->minutes();

    expect(fn () => app(StartAttemptAction::class)->handle($student, $exam))->toThrow(Exception::class)
        ->and($attempt->fresh()->finished)->toBeTrue()
        ->and((float) $attempt->fresh()->student_percentage)->toBe(100.0);
});

it('أمر الجدولة بيسلّم المحاولات المنتهية', function (): void {
    $student = Student::factory()->create();
    $exam = OnlineExam::factory()->create(['duration_in_seconds' => 60]);
    Question::factory()->withChoices()->create(['online_exam_id' => $exam->getKey()]);
    $attempt = app(StartAttemptAction::class)->handle($student, $exam);

    $this->travel(10)->minutes();
    $this->artisan('exams:finalize-expired')->assertSuccessful();

    expect($attempt->fresh()->finished)->toBeTrue();
});
