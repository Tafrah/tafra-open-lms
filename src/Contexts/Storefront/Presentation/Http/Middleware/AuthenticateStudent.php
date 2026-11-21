<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Src\Contexts\Students\Domain\Models\Student;
use Symfony\Component\HttpFoundation\Response;

/**
 * منطقة الطالب: لازم يكون داخل على حارس `student`. الضيف بيروح لصفحة
 * دخول الموقع (مش دخول اللوحة)، والحساب اللي اتوقف وهو داخل بيتطلع برا.
 *
 * لازم ييجي **بعد** `ResolveStorefrontTenant` — الطالب موديل تابع لمؤسسة.
 */
final class AuthenticateStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('student');
        $student = $guard->user();

        if (! $student instanceof Student) {
            return $request->expectsJson()
                ? response()->json(['message' => __('storefront::storefront.errors.login_required')], 401)
                : redirect()->guest(route('site.login'));
        }

        if ($student->isBlocked()) {
            $guard->logout();
            $request->session()->regenerateToken();

            return redirect()->route('site.login')->withErrors(['phone' => __('storefront::storefront.errors.blocked')]);
        }

        return $next($request);
    }
}
