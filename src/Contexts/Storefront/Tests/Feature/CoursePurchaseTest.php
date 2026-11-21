<?php

declare(strict_types=1);

require_once __DIR__.'/../storefront-helpers.php';

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Contexts\Billing\Application\Actions\CheckoutAction;
use Src\Contexts\Billing\Application\Actions\ReviewInvoiceAction;
use Src\Contexts\Billing\Application\Actions\WalletAction;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Enums\ReceiptStatus;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Models\Coupon;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;
use Src\Contexts\Billing\Domain\Models\PaymentReceipt;
use Src\Contexts\Storefront\Presentation\Livewire\PurchaseCard;
use Src\Support\Application\Contracts\DiskResolver;

beforeEach(function (): void {
    inTenant();
    Storage::fake(app(DiskResolver::class)->for('receipt'));
    Storage::fake('public');
});

it('الضيف بيشوف الكورس وزرار «سجّل دخول عشان تشترك»', function (): void {
    $course = sfCourse(['name' => 'Waves 101', 'price' => 200]);

    $this->get(route('site.courses.show', $course->slug))
        ->assertOk()
        ->assertSee('Waves 101')
        ->assertSee('Intro video')
        ->assertSee(__('storefront::storefront.purchase.login_to_buy'));
});

it('المشترك بيشوف «ادخل الكورس» بدل أزرار الدفع', function (): void {
    $course = sfCourse();
    $student = sfLogin();
    sfEnroll($student, $course);

    $this->get(route('site.courses.show', $course->slug))
        ->assertOk()
        ->assertSee(__('storefront::storefront.purchase.enter'))
        ->assertDontSee(__('storefront::storefront.purchase.pay_wallet'));
});

it('كورس مسودة أو مش موجود = 404', function (): void {
    $draft = sfCourse(['publish_status' => 'draft']);

    $this->get(route('site.courses.show', $draft->slug))->assertNotFound();
    $this->get(route('site.courses.show', 'nope'))->assertNotFound();
});

it('صفحة الكورس عدد استعلاماتها ثابت مهما زاد عدد الدروس', function (): void {
    $small = sfCourse();
    $large = sfCourse();
    for ($i = 0; $i < 15; $i++) {
        sfLessonItem($large, []);
    }
    sfLogin();

    $count = function (string $slug): int {
        app(BootcampAccess::class)->forget();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('site.courses.show', $slug))->assertOk();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };

    // أول طلب بيسخّن كاش الإعدادات — بعده العدد لازم يبقى ثابت
    $count($small->slug);

    expect($count($large->slug))->toBe($count($small->slug));
});

it('الشراء من المحفظة عبر HTTP بيخصم ويفتح الكورس', function (): void {
    $course = sfCourse(['price' => 150]);
    $student = sfLogin();
    app(WalletAction::class)->deposit($student, 200, TransactionSource::Admin);

    $this->post(route('site.checkout.wallet', $course->slug))
        ->assertRedirect(route('site.learn.course', $course->slug));

    expect((float) $student->fresh()->balance)->toBe(50.0)
        ->and(app(BootcampAccess::class)->has($student, $course))->toBeTrue();
});

it('رصيد مش كفاية بيرجّع رسالة ومابيفتحش', function (): void {
    $course = sfCourse(['price' => 150]);
    $student = sfLogin();

    $this->post(route('site.checkout.wallet', $course->slug))
        ->assertRedirect(route('site.courses.show', $course->slug))
        ->assertSessionHas('error');

    expect(app(BootcampAccess::class)->has($student, $course))->toBeFalse();
});

it('الكوبون بيتطبّق على الشراء من المحفظة', function (): void {
    $course = sfCourse(['price' => 100]);
    $student = sfLogin();
    app(WalletAction::class)->deposit($student, 100, TransactionSource::Admin);
    Coupon::factory()->create(['code' => 'EID50', 'value' => 50]);

    $this->post(route('site.checkout.wallet', $course->slug), ['coupon' => 'eid50'])->assertRedirect();

    expect((float) $student->fresh()->balance)->toBe(50.0);
});

it('«اشترك مجانًا» على كورس مدفوع مابيفتحهوش ببلاش', function (): void {
    $course = sfCourse(['price' => 300]);
    $student = sfLogin();

    $this->post(route('site.checkout.free', $course->slug));

    expect(app(BootcampAccess::class)->has($student, $course))->toBeFalse();
});

it('الكورس المجاني بيتفتح بضغطة', function (): void {
    $course = sfCourse(['price' => 0]);
    $student = sfLogin();

    $this->post(route('site.checkout.free', $course->slug))->assertRedirect(route('site.learn.course', $course->slug));

    expect(app(BootcampAccess::class)->has($student, $course))->toBeTrue();
});

it('كارت الشراء بيحسب الكوبون حي وبيرفض الغلط', function (): void {
    $course = sfCourse(['price' => 200]);
    sfLogin();
    Coupon::factory()->create(['code' => 'SAVE20', 'value' => 20]);

    Livewire::test(PurchaseCard::class, ['bootcampId' => $course->getKey()])
        ->set('coupon', 'WRONG')
        ->call('applyCoupon')
        ->assertHasErrors('coupon')
        ->assertSet('appliedCoupon', null)
        ->set('coupon', 'save20')
        ->call('applyCoupon')
        ->assertHasNoErrors()
        ->assertSet('appliedCoupon', 'SAVE20')
        ->assertSee(fc_currency(160.0, 'EGP'))
        ->assertSee('value="SAVE20"', escape: false);
});

it('التحويل بالإيصال: فاتورة ← رفع الصورة ← مستني مراجعة ← اتقبل', function (): void {
    $course = sfCourse(['price' => 250]);
    $student = sfLogin();
    $method = PaymentMethod::factory()->create(['name' => 'Vodafone Cash', 'account_number' => '01055555555']);
    PaymentMethod::factory()->create(['name' => 'Disabled method', 'is_active' => false]);

    $response = $this->post(route('site.checkout.transfer', $course->slug));
    $invoice = PaymentInvoice::query()->where('student_id', $student->getKey())->firstOrFail();
    $response->assertRedirect(route('site.checkout.show', $invoice->number));

    $this->get(route('site.checkout.show', $invoice->number))
        ->assertOk()
        ->assertSee('01055555555')
        ->assertSee('Vodafone Cash')
        ->assertDontSee('Disabled method')
        ->assertSee(fc_currency(250.0, 'EGP'));

    $this->post(route('site.checkout.receipt', $invoice->number), [
        'payment_method_id' => $method->getKey(),
        'receipt' => UploadedFile::fake()->image('receipt.jpg'),
        'sender_phone' => '01011112222',
        'reference' => 'TX-123',
    ])->assertRedirect(route('site.checkout.show', $invoice->number))->assertSessionHas('status');

    $receipt = PaymentReceipt::query()->where('payment_invoice_id', $invoice->getKey())->firstOrFail();
    expect($receipt->status)->toBe(ReceiptStatus::Pending)
        ->and($receipt->sender_phone)->toBe('01011112222')
        ->and($receipt->getFirstMedia('receipt'))->not->toBeNull()
        // صورة الإيصال (فيها أرقام الطالب) على الديسك الخاص — مش العام
        ->and(app(DiskResolver::class)->isPrivate('receipt'))->toBeTrue()
        ->and($receipt->getFirstMedia('receipt')->disk)->toBe(app(DiskResolver::class)->for('receipt'));

    $this->get(route('site.checkout.show', $invoice->number))->assertSee(__('storefront::storefront.checkout.waiting_title'));

    app(ReviewInvoiceAction::class)->approve($invoice->fresh(), userWithRole('admin'));
    inTenant();

    $this->get(route('site.checkout.show', $invoice->number))->assertSee(__('storefront::storefront.checkout.approved_title'));
    expect(app(BootcampAccess::class)->has($student, $course))->toBeTrue();
});

it('الإيصال بيترفض لو مش صورة أو أكبر من ٥ ميجا', function (): void {
    $course = sfCourse(['price' => 250]);
    sfLogin();
    $method = PaymentMethod::factory()->create();
    $this->post(route('site.checkout.transfer', $course->slug));
    $invoice = PaymentInvoice::query()->firstOrFail();

    $this->post(route('site.checkout.receipt', $invoice->number), [
        'payment_method_id' => $method->getKey(),
        'receipt' => UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml'),
        'sender_phone' => '01011112222',
    ])->assertSessionHasErrors('receipt');

    $this->post(route('site.checkout.receipt', $invoice->number), [
        'payment_method_id' => $method->getKey(),
        'receipt' => UploadedFile::fake()->image('big.jpg')->size(6000),
        'sender_phone' => '01011112222',
    ])->assertSessionHasErrors('receipt');

    expect(PaymentReceipt::query()->count())->toBe(0);
});

it('فاتورة طالب تاني = 404 (عرض ورفع)', function (): void {
    $course = sfCourse(['price' => 250]);
    $owner = sfStudent();
    $method = PaymentMethod::factory()->create();
    $invoice = app(CheckoutAction::class)->handle($owner, $course, PaymentGateway::ManualTransfer);

    sfLogin();

    $this->get(route('site.checkout.show', $invoice->number))->assertNotFound();
    $this->post(route('site.checkout.receipt', $invoice->number), [
        'payment_method_id' => $method->getKey(),
        'receipt' => UploadedFile::fake()->image('r.jpg'),
        'sender_phone' => '01011112222',
    ])->assertNotFound();
});

it('الإيصال المرفوض بيظهر سببه، و«حاول تاني» بتعمل فاتورة جديدة', function (): void {
    $course = sfCourse(['price' => 250]);
    $student = sfLogin();
    $this->post(route('site.checkout.transfer', $course->slug));
    $invoice = PaymentInvoice::query()->firstOrFail();

    app(ReviewInvoiceAction::class)->reject($invoice, userWithRole('admin'), 'Blurry image');
    inTenant();

    $this->get(route('site.checkout.show', $invoice->number))->assertOk()->assertSee('Blurry image');

    $this->post(route('site.checkout.retry', $invoice->number))->assertRedirect();

    $fresh = PaymentInvoice::query()->where('student_id', $student->getKey())->latest('id')->firstOrFail();
    expect($fresh->getKey())->not->toBe($invoice->getKey())
        ->and($fresh->status)->toBe(InvoiceStatus::Pending);
});

it('الضيف مايقدرش يشتري', function (): void {
    $course = sfCourse();

    $this->post(route('site.checkout.wallet', $course->slug))->assertRedirect(route('site.login'));
    expect(PaymentInvoice::query()->count())->toBe(0);
});
