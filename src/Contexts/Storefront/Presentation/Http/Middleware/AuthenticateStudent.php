<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Src\Contexts\Storefront\Domain\Exceptions\StorefrontException;
use Src\Contexts\Storefront\Presentation\Support\StudentDeviceSession;
use Src\Contexts\Students\Domain\Models\Student;
use Symfony\Component\HttpFoundation\Response;

/**
 * منطقة الطالب: لازم يكون داخل على حارس `student`. الضيف بيروح لصفحة
 * دخول الموقع (مش دخول اللوحة)، والحساب اللي اتوقف وهو داخل — أو جهازه
 * اتفصل من اللوحة — بيتطلع برا.
 *
 * لازم ييجي **بعد** `ResolveStorefrontTenant` — الطالب موديل تابع لمؤسسة.
 */
final class AuthenticateStudent
{
    public function __construct(private readonly StudentDeviceSession $devices) {}

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
            return $this->kickOut($request, __('storefront::storefront.errors.blocked'));
        }

        // «جهاز واحد لكل طالب»: المدرس فصل الجهاز ← خروج في أول طلب
        try {
            if (! $this->devices->ensure($request, $student)) {
                return $this->kickOut($request, __('storefront::storefront.errors.device_disconnected'));
            }
        } catch (StorefrontException $e) {
            return $this->kickOut($request, $e->getMessage());
        }

        return $next($request);
    }

    private function kickOut(Request $request, string $message): Response
    {
        Auth::guard('student')->logout();
        $this->devices->forget($request);
        $request->session()->regenerateToken();

        return $request->expectsJson()
            ? response()->json(['message' => $message], 401)
            : redirect()->route('site.login')->withErrors(['phone' => $message]);
    }
}
