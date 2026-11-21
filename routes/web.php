<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Contexts\Audit\Presentation\Http\Controllers\HealthEndpointController;

Route::redirect('/', '/admin');

/*
|--------------------------------------------------------------------------
| نقطة فحص الصحة لمراقب التشغيل
|--------------------------------------------------------------------------
| محمية بمفتاح من `HEALTH_ENDPOINT_SECRET`. لو المفتاح مش مضبوط،
| النقطة مقفولة بالكامل (404) — الافتراضي الآمن.
*/
Route::get('/health', HealthEndpointController::class)->name('health.json');
