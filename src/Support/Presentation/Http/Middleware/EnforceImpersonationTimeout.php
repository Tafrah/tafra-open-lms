<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Contexts\Settings\Domain\Settings\SecuritySettings;
use Src\Support\Infrastructure\Authorization\ImpersonationContext;
use STS\FilamentImpersonate\Facades\Impersonation;
use Symfony\Component\HttpFoundation\Response;

/**
 * الجلسة المنتحلة بتنتهي تلقائياً بعد المدة القصوى (docs/12 بند ٣ نقطة ٥).
 */
final class EnforceImpersonationTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(ImpersonationContext::class);

        if (! $context->isActive()) {
            return $next($request);
        }

        if ($context->startedAt() === null) {
            $context->markStarted();

            return $next($request);
        }

        if (! $context->hasExpired($this->maxMinutes())) {
            return $next($request);
        }

        // مكان الرجوع قبل `leave()` — فريق التشغيل بيرجع لـ /central مش
        // لـ `/admin` اللي مالوش فيها مؤسسات (docs/24 بند ٤)
        $backTo = $context->pullBackTo();

        Impersonation::leave();
        $context->forget();

        activity('security')->log('impersonation_expired');

        return redirect()->to($backTo ?? (string) config('filament-impersonate.redirect_to', '/'));
    }

    private function maxMinutes(): int
    {
        try {
            return app(SecuritySettings::class)->impersonation_max_minutes;
        } catch (\Throwable) {
            return (int) config('security.impersonation.max_minutes', 30);
        }
    }
}
