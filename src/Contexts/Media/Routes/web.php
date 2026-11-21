<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Contexts\Media\Presentation\Http\Controllers\ServePrivateMediaController;

/*
|--------------------------------------------------------------------------
| رابط مؤقت للملفات الخاصة على الديسكات المحلية
|--------------------------------------------------------------------------
| S3 بيدي `getTemporaryUrl()` جاهز. الديسك المحلي مافيهوش، فبنعمل
| route موقّع بديل — ومع ذلك بيعدّي على الـ Policy (docs/04 بند ٦).
*/

Route::middleware(['web', 'auth', 'signed'])
    ->get('/media/{media}/private', ServePrivateMediaController::class)
    ->name('media.private');
