<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Contexts\Storefront\Presentation\Http\Controllers\AuthController;
use Src\Contexts\Storefront\Presentation\Http\Controllers\CheckoutController;
use Src\Contexts\Storefront\Presentation\Http\Controllers\CourseController;
use Src\Contexts\Storefront\Presentation\Http\Controllers\ExamController;
use Src\Contexts\Storefront\Presentation\Http\Controllers\HomeController;
use Src\Contexts\Storefront\Presentation\Http\Controllers\LearnController;
use Src\Contexts\Storefront\Presentation\Http\Controllers\LocaleController;
use Src\Contexts\Storefront\Presentation\Http\Controllers\StudentAreaController;
use Src\Contexts\Storefront\Presentation\Http\Controllers\WalletController;
use Src\Contexts\Storefront\Presentation\Http\Middleware\AuthenticateStudent;
use Src\Contexts\Storefront\Presentation\Http\Middleware\RedirectIfStudent;
use Src\Contexts\Storefront\Presentation\Http\Middleware\ResolveStorefrontTenant;
use Src\Contexts\Storefront\StorefrontServiceProvider;

/*
|--------------------------------------------------------------------------
| الموقع العام للطلبة (docs/product/plan.html#student · #landing)
|--------------------------------------------------------------------------
| `ResolveStorefrontTenant` قبل أي حاجة: الطالب والكورسات موديلات تابعة
| لمؤسسة. مفيش route model binding هنا عن قصد — `SubstituteBindings` في
| ستاك `web` بيشتغل قبل ما المؤسسة تتحدد، فالبحث بيحصل جوه الكنترولر.
| كل POST عليه throttle وCSRF (ستاك web).
*/

// مرجع ثابت لطلبات Livewire من مكوّنات الموقع (StorefrontServiceProvider) — مابيتفتحش
Route::get(StorefrontServiceProvider::LIVEWIRE_CONTEXT_PATH, static fn () => abort(404))->name('site.livewire-context');

Route::middleware(['web', ResolveStorefrontTenant::class])->name('site.')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::post('/locale', LocaleController::class)->middleware('throttle:storefront-forms')->name('locale');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses');
    Route::get('/courses/{slug}', [CourseController::class, 'show'])->name('courses.show');

    Route::middleware(RedirectIfStudent::class)->group(function (): void {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:storefront-forms')->name('login.store');
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:storefront-forms')->name('register.store');
        Route::get('/forgot-password', [AuthController::class, 'forgot'])->name('password.forgot');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(AuthenticateStudent::class)->group(function (): void {
        Route::get('/dashboard', [StudentAreaController::class, 'dashboard'])->name('dashboard');
        Route::get('/my-courses', [StudentAreaController::class, 'courses'])->name('my-courses');
        Route::get('/invoices', [StudentAreaController::class, 'invoices'])->name('invoices');
        Route::get('/exams', [StudentAreaController::class, 'exams'])->name('exams');
        Route::get('/profile', [StudentAreaController::class, 'profile'])->name('profile');
        Route::put('/profile', [StudentAreaController::class, 'updateProfile'])->middleware('throttle:storefront-forms')->name('profile.update');
        Route::put('/profile/password', [StudentAreaController::class, 'updatePassword'])->middleware('throttle:storefront-forms')->name('profile.password');

        Route::get('/wallet', [WalletController::class, 'show'])->name('wallet');
        Route::post('/wallet/redeem', [WalletController::class, 'redeem'])->middleware('throttle:storefront-forms')->name('wallet.redeem');

        Route::middleware('throttle:storefront-forms')->group(function (): void {
            Route::post('/courses/{slug}/wallet', [CheckoutController::class, 'wallet'])->name('checkout.wallet');
            Route::post('/courses/{slug}/transfer', [CheckoutController::class, 'transfer'])->name('checkout.transfer');
            Route::post('/courses/{slug}/free', [CheckoutController::class, 'free'])->name('checkout.free');
            Route::post('/checkout/{number}/receipt', [CheckoutController::class, 'receipt'])->name('checkout.receipt');
            Route::post('/checkout/{number}/retry', [CheckoutController::class, 'retry'])->name('checkout.retry');
        });
        Route::get('/checkout/{number}', [CheckoutController::class, 'show'])->name('checkout.show');

        Route::get('/learn/{slug}', [LearnController::class, 'course'])->name('learn.course');
        Route::get('/learn/{slug}/{item}', [LearnController::class, 'show'])->whereNumber('item')->name('learn');
        Route::post('/learn/{slug}/{item}/complete', [LearnController::class, 'complete'])->whereNumber('item')->middleware('throttle:storefront-forms')->name('learn.complete');
        Route::get('/learn/{slug}/{item}/document', [LearnController::class, 'document'])->whereNumber('item')->middleware('signed')->name('learn.document');

        Route::get('/exams/{item}/start', [ExamController::class, 'start'])->whereNumber('item')->name('exams.start');
        Route::post('/exams/{item}/start', [ExamController::class, 'begin'])->whereNumber('item')->middleware('throttle:storefront-forms')->name('exams.begin');
        Route::get('/attempts/{attempt}', [ExamController::class, 'attempt'])->whereNumber('attempt')->name('attempts.show');
        Route::get('/attempts/{attempt}/result', [ExamController::class, 'result'])->whereNumber('attempt')->name('attempts.result');
    });
});
