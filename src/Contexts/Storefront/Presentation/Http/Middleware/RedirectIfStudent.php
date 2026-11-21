<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** صفحات الدخول والتسجيل للضيف بس — الطالب الداخل بيروح للوحته. */
final class RedirectIfStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('student')->check()) {
            return redirect()->route('site.dashboard');
        }

        return $next($request);
    }
}
