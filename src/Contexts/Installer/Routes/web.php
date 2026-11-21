<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Contexts\Installer\Presentation\Http\Controllers\InstallerController;
use Src\Contexts\Installer\Presentation\Http\Controllers\ServePublicFileController;
use Src\Contexts\Installer\Presentation\Http\Middleware\EnsureNotInstalled;

/*
|--------------------------------------------------------------------------
| المثبّت — `/install/*` بيرجّع 404 بعد التثبيت
|--------------------------------------------------------------------------
*/
Route::middleware([EnsureNotInstalled::class, 'web', 'throttle:60,1'])
    ->prefix('install')
    ->name('installer.')
    ->controller(InstallerController::class)
    ->group(static function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('requirements', 'requirements')->name('requirements');
        Route::get('database', 'database')->name('database');
        Route::post('database', 'saveDatabase')->name('database.save');
        Route::get('academy', 'academy')->name('academy');
        Route::post('academy', 'install')->name('install');
        Route::get('lang/{locale}', 'switchLocale')->name('locale')->whereIn('locale', (array) config('app.supported_locales', ['ar', 'en']));
    });

/*
|--------------------------------------------------------------------------
| ملفات الديسك العام من غير symlink
|--------------------------------------------------------------------------
| لو الاستضافة قافلة `symlink()`، `PUBLIC_STORAGE_URL` بيشاور هنا. مش جوه
| `web`: مفيش جلسة ولا كوكيز لملف عام. `/storage/{path}` نفسه محجوز لـ
| Laravel (ديسك `local` بـ `serve => true`، بروابط موقّعة).
*/
Route::get(config('installer.public_files.prefix', 'uploads').'/{path}', ServePublicFileController::class)
    ->where('path', '.*')
    ->name('installer.public-file');
