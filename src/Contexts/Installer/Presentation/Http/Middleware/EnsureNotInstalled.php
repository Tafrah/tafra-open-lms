<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Contexts\Installer\Infrastructure\InstallationState;
use Symfony\Component\HttpFoundation\Response;

/** بعد التثبيت المثبّت مش موجود — 404، مش رسالة «اتثبّت قبل كده». */
final readonly class EnsureNotInstalled
{
    public function __construct(private InstallationState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_if($this->state->isInstalled(), 404);

        return $next($request);
    }
}
