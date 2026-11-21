<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Http\Middleware;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Src\Contexts\Identity\Presentation\Filament\Pages\TwoFactorSettings;
use Src\Contexts\Settings\Domain\Settings\SecuritySettings;
use Src\Support\Infrastructure\Authorization\CentralRoles;
use Symfony\Component\HttpFoundation\Response;

/**
 * الأدوار الإدارية مجبرة على 2FA بعد فترة سماح — بيوجّه لصفحة الإعداد
 * بدل ما يقفل الحساب (docs/12 بند ١).
 */
final class EnsureTwoFactorIsConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $this->isTwoFactorPage($request)) {
            return $next($request);
        }

        if ($user->two_factor_confirmed_at !== null) {
            return $next($request);
        }

        [$requiredRoles, $graceDays] = $this->policy();

        if ($requiredRoles === [] || ! $this->needsTwoFactor($user, $requiredRoles)) {
            return $next($request);
        }

        if ($user->created_at?->addDays($graceDays)->isFuture()) {
            return $next($request);
        }

        Notification::make()
            ->warning()
            ->title(__('identity::identity.two_factor.required_title'))
            ->body(__('identity::identity.two_factor.required_body'))
            ->persistent()
            ->send();

        return redirect()->to(TwoFactorSettings::getUrl());
    }

    private function isTwoFactorPage(Request $request): bool
    {
        return $request->routeIs('*two-factor*')
            || $request->routeIs('*.logout')
            || $request->is('livewire/*');
    }

    /**
     * الفحص ده على **الأدوار** لأنه شرط دخول للوحة كلها، مش قدرة على سجل.
     *
     * @param list<string> $requiredRoles
     */
    private function needsTwoFactor(object $user, array $requiredRoles): bool
    {
        if (! method_exists($user, 'getRoleNames') || ! $user instanceof Authenticatable) {
            return false;
        }

        // أدوار المؤسسة الحالية + أدوار فريق التشغيل (الفريق المركزي)
        return $user->getRoleNames()
            ->merge(app(CentralRoles::class)->names($user))
            ->intersect($requiredRoles)
            ->isNotEmpty();
    }

    /** @return array{0: list<string>, 1: int} */
    private function policy(): array
    {
        try {
            $settings = app(SecuritySettings::class);

            return [$settings->two_factor_required_roles, $settings->two_factor_grace_period_days];
        } catch (\Throwable) {
            return [
                (array) config('security.two_factor.required_for_roles', []),
                (int) config('security.two_factor.grace_period_days', 7),
            ];
        }
    }
}
