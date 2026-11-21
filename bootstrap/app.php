<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Src\Support\Presentation\Http\Middleware\AssignRequestContext;
use Src\Support\Presentation\Http\Middleware\EndTenancy;
use Src\Support\Presentation\Http\Middleware\InitializeTenantContext;
use Src\Support\Presentation\Http\Middleware\SecurityHeaders;
use Src\Support\Presentation\Http\Middleware\SetLocale;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
        |----------------------------------------------------------------------
        | البروكسي — أشهر باگ في الإنتاج
        |----------------------------------------------------------------------
        | لو التطبيق ورا load balancer بينهي الـ TLS، Laravel بيشوف الطلب
        | HTTP فكل الروابط بتتولّد `http://` وبتتكسر (docs/14 بند ١).
        |
        | ⚠️ **قائمة البروكسيات نفسها مش هنا** — الـ closure دي بتشتغل قبل ما
        | الكونفيج يتحمّل، و`env()` بترجّع null مع `config:cache`، يعني أي
        | CIDR صريح كان هيتحوّل بصمت لـ `*` في الإنتاج بالظبط. القائمة
        | بتتضبط في `AppServiceProvider::boot()` من `config('security.trusted_proxies')`.
        | هنا بنحدد **الرؤوس** بس — دي ثوابت مالهاش علاقة بالكونفيج.
        */
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        // ملاحظة: `append` مش `use` — `use()` بيستبدل الستاك كله
        // فـ TrustProxies وغيره مايشتغلوش.
        $middleware->web(append: [
            SetLocale::class,
            AssignRequestContext::class,
            SecurityHeaders::class,
        ]);

        /*
        | ⚠️ سياق المستأجر لازم يتضبط **قبل** `Authenticate`.
        |
        | Laravel بيرتّب `Authenticate` بأولوية فوق باقي ميدلوير اللوحة، و
        | `canAccessPanel()` بيسأل عن صلاحية على **فريق spatie الحالي**. من
        | غير السطرين دول الفريق كان لسه فاضي في أول طلب لأي عامل → 403
        | لكل المستخدمين، واللوحة المركزية كانت هتسأل على فريق مؤسسة
        | (docs/21 بند ٣٠ · docs/24 بند ١).
        */
        $middleware->prependToPriorityList(before: AuthenticatesRequests::class, prepend: InitializeTenantContext::class);
        $middleware->prependToPriorityList(before: AuthenticatesRequests::class, prepend: EndTenancy::class);

        /*
        | الضيف على صفحة خارج اللوحة (موافقة OAuth لتطبيق ذكاء اصطناعي) بيروح
        | لدخول اللوحة — Filament مالوش route اسمه `login`. بعد الدخول
        | `redirect()->intended()` بيرجّعه لشاشة الموافقة (docs/23 بند ٤).
        */
        $middleware->redirectGuestsTo(fn (): string => route('filament.admin.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // ممنوع تسجيل الحقول الحساسة في تقارير الأخطاء
        $exceptions->dontFlash([
            'current_password', 'password', 'password_confirmation',
            'two_factor_secret', 'two_factor_recovery_codes', 'code',
        ]);
    })->create();
