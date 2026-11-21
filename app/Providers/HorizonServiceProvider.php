<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

final class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * الدخول لـ Horizon بيمرّ على نفس نظام التفويض —
     * مفيش قائمة إيميلات مكتوبة في الكود.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', static fn (mixed $user = null): bool => $user !== null
            && Gate::forUser($user)->allows('access.horizon'));
    }
}
