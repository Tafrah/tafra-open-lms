<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Contexts\Demo\Presentation\Http\Controllers\DemoLoginController;
use Src\Contexts\Storefront\Presentation\Http\Middleware\ResolveStorefrontTenant;

/*
|--------------------------------------------------------------------------
| دخول نسخة التجربة بضغطة — 404 لو المنصة مش نسخة تجربة (DemoMode::active)
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'throttle:30,1'])->prefix('demo/login')->name('demo.login.')->group(static function (): void {
    Route::post('student', [DemoLoginController::class, 'student'])
        ->middleware(ResolveStorefrontTenant::class)
        ->name('student');

    Route::post('{account}', [DemoLoginController::class, 'staff'])
        ->whereIn('account', ['teacher', 'assistant', 'viewer'])
        ->name('staff');
});
