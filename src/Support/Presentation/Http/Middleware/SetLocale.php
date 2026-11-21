<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Http\Middleware;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Src\Contexts\Settings\Domain\Settings\GeneralSettings;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $supported = (array) config('app.supported_locales', ['ar', 'en']);

        $candidates = [
            $request->user()?->locale,
            $request->hasSession() ? $request->session()->get('locale') : null,
            $this->settingsLocale(),
            config('app.locale'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && in_array($candidate, $supported, true)) {
                return $candidate;
            }
        }

        return (string) config('app.fallback_locale');
    }

    private function settingsLocale(): ?string
    {
        try {
            return app(GeneralSettings::class)->default_locale;
        } catch (\Throwable) {
            return null;
        }
    }
}
