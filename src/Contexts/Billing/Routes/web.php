<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Contexts\Billing\Presentation\Http\Controllers\RechargeCardExportController;
use Src\Support\Presentation\Http\Middleware\InitializeTenantContext;

/*
| طباعة/تنزيل أكواد الشحن — لمستخدمي اللوحة بس (حارس web)، والـ Policy جوه الكنترولر.
*/
Route::middleware(['web', 'auth', InitializeTenantContext::class])
    ->prefix('admin/recharge-cards/{group}')
    ->name('billing.recharge-cards.')
    ->group(function (): void {
        Route::get('print', [RechargeCardExportController::class, 'print'])->name('print');
        Route::get('csv', [RechargeCardExportController::class, 'csv'])->name('csv');
    });
