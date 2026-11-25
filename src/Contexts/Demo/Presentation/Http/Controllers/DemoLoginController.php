<?php

declare(strict_types=1);

namespace Src\Contexts\Demo\Presentation\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Src\Contexts\Demo\Application\DemoMode;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Storefront\Domain\Exceptions\StorefrontException;
use Src\Contexts\Storefront\Presentation\Support\StudentDeviceSession;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * «ادخل كـ…» في نسخة التجربة — POST بـ CSRF، والحساب من config/demo.php بس.
 *
 * مفيش أي مدخل من الزائر غير مفتاح الحساب (teacher / assistant / viewer / student)،
 * وكله 404 لو المنصة مش نسخة تجربة (DemoMode::active).
 */
final class DemoLoginController
{
    public function staff(Request $request, string $account, DemoMode $demo): RedirectResponse
    {
        $config = $this->account($demo, $account, 'web');

        $user = User::query()->where('email', $config['email'] ?? '')->first();
        abort_if($user === null, 404);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->to(Filament::getPanel('admin')->getUrl() ?? url('/admin'));
    }

    public function student(Request $request, DemoMode $demo, StudentDeviceSession $devices): RedirectResponse
    {
        $config = $this->account($demo, 'student', 'student');

        $student = Student::query()->where('phone', $config['phone'] ?? '')->first();
        abort_if($student === null, 404);

        try {
            $devices->register($request, $student);
        } catch (StorefrontException $e) {
            throw ValidationException::withMessages(['phone' => $e->getMessage()]);
        }

        Auth::guard('student')->login($student);
        $request->session()->regenerate();

        return redirect()->route('site.dashboard');
    }

    /** @return array<string, string> */
    private function account(DemoMode $demo, string $key, string $guard): array
    {
        abort_unless($demo->active(), 404);

        $account = $demo->account($key);
        abort_if($account === null || ($account['guard'] ?? '') !== $guard, 404);

        return $account;
    }
}
