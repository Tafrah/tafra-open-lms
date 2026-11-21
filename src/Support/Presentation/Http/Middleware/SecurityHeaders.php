<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * الرؤوس الأمنية (docs/12 بند ٦ · docs/20 بند ٥).
 *
 * CSP بيتبعت في وضع **التقرير** افتراضياً — Filament و Livewire بيستخدموا
 * سكربتات inline، والفرض من غير مرحلة تقرير بيكسر اللوحة.
 * حوّل `security.csp.enforce` لـ true بعد ما التقارير تنضف.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->add([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), interest-cohort=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ]);

        // HSTS في الإنتاج بس — على HTTP محلياً بيكسر التطوير
        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload',
            );
        }

        if (config('security.csp.enabled', true)) {
            $header = config('security.csp.enforce', false)
                ? 'Content-Security-Policy'
                : 'Content-Security-Policy-Report-Only';

            $response->headers->set($header, $this->policy());
        }

        return $response;
    }

    private function policy(): string
    {
        $extra = (array) config('security.csp.extra_sources', []);

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'],
            'font-src' => ["'self'", 'data:', 'https://fonts.gstatic.com'],
            'img-src' => ["'self'", 'data:', 'blob:'],
            'connect-src' => ["'self'", 'ws:', 'wss:'],
            'media-src' => ["'self'", 'blob:'],
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'object-src' => ["'none'"],
        ];

        foreach ($extra as $directive => $sources) {
            $directives[$directive] = [...($directives[$directive] ?? []), ...(array) $sources];
        }

        return collect($directives)
            ->map(fn (array $sources, string $directive): string => $directive.' '.implode(' ', array_unique($sources)))
            ->implode('; ');
    }
}
