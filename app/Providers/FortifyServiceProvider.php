<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

/**
 * إحنا بنستخدم من Fortify **منطق 2FA بس** — الشاشات كلها في Filament،
 * والدخول والخروج بتاع اللوحة من Filament نفسه (docs/12 بند ١).
 *
 * `ignoreRoutes()` بيمنع Fortify من تسجيل مسارات موازية لمسارات اللوحة.
 */
final class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Fortify::ignoreRoutes();
    }
}
