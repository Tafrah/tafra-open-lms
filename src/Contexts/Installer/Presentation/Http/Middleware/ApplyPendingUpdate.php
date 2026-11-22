<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Src\Contexts\Installer\Application\Actions\ApplyUpdateAction;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * أول طلب بعد رفع إصدار جديد بيطبّق التحديث (ApplyUpdateAction).
 *
 * ميدلوير عام بعد `RedirectToInstaller` — قبل الجلسة: جدول الجلسات أو
 * الكاش نفسه ممكن يكون جاي في الميجريشنز الجديدة.
 */
final readonly class ApplyPendingUpdate
{
    public function __construct(private ApplyUpdateAction $update) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('up', 'health') || ! $this->update->needed()) {
            return $next($request);
        }

        try {
            $result = $this->update->handle();
        } catch (Throwable) {
            return $this->page('failed', 500);
        }

        if ($result === ApplyUpdateAction::BUSY) {
            return $this->page('busy', 503);
        }

        return $next($request);
    }

    private function page(string $state, int $status): Response
    {
        return new HttpResponse(view('installer::updating', ['state' => $state])->render(), $status, [
            'Retry-After' => '30',
            'Cache-Control' => 'no-store',
        ]);
    }
}
