<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Src\Contexts\Storefront\Application\Actions\AuthenticateStudentAction;
use Src\Contexts\Storefront\Application\DataObjects\SiteSettingsData;
use Src\Contexts\Storefront\Application\Queries\CourseCatalog;
use Src\Contexts\Storefront\Domain\Exceptions\StorefrontException;
use Src\Contexts\Students\Application\Actions\RegisterStudentAction;
use Src\Contexts\Students\Domain\Enums\SchoolType;
use Src\Contexts\Students\Domain\Exceptions\StudentsException;

/**
 * دخول الطالب وتسجيله (موبايل + كلمة سر، على حارس `student`).
 * مفيش SMS في النسخة المجانية: «نسيت كلمة السر» = كلّم المدرس على واتساب.
 */
final class AuthController
{
    /** موبايل مصري بعد التطبيع (`RegisterStudentAction::normalizePhone`) */
    public const PHONE_PATTERN = '/^01[0125][0-9]{8}$/';

    public function showLogin(Request $request): View
    {
        if (is_string($next = $request->query('next')) && self::isSafePath($next)) {
            $request->session()->put('url.intended', url($next));
        }

        return view('storefront::pages.auth.login');
    }

    public function login(Request $request, AuthenticateStudentAction $authenticate): RedirectResponse
    {
        $request->merge(['phone' => RegisterStudentAction::normalizePhone((string) $request->input('phone'))]);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ], [], $this->attributes());

        $key = 'storefront-login:'.sha1($data['phone'].'|'.$request->ip());
        $max = (int) config('storefront.limits.login_attempts', 5);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw ValidationException::withMessages([
                'phone' => __('storefront::storefront.errors.too_many_logins', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        try {
            $student = $authenticate->handle($data['phone'], $data['password']);
        } catch (StorefrontException $e) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['phone' => $e->getMessage()]);
        }

        RateLimiter::clear($key);

        Auth::guard('student')->login($student, (bool) ($data['remember'] ?? false));
        $request->session()->regenerate();

        return redirect()->intended(route('site.dashboard'))
            ->with('status', __('storefront::storefront.auth.welcome_back', ['name' => $student->name]));
    }

    public function showRegister(CourseCatalog $catalog): View
    {
        return view('storefront::pages.auth.register', [
            'levels' => $catalog->levels(),
            'governorates' => (array) __('storefront::storefront.governorates'),
        ]);
    }

    public function register(Request $request, RegisterStudentAction $register, CourseCatalog $catalog): RedirectResponse
    {
        /** @var SiteSettingsData $site */
        $site = view()->shared('site');

        $request->merge(array_filter([
            'phone' => RegisterStudentAction::normalizePhone((string) $request->input('phone')),
            'parent_phone' => filled($request->input('parent_phone')) ? RegisterStudentAction::normalizePhone((string) $request->input('parent_phone')) : null,
        ], fn (?string $value): bool => $value !== null));

        $levelIds = $catalog->levels()->modelKeys();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['required', 'string', 'regex:'.self::PHONE_PATTERN],
            'parent_phone' => [$site->parentPhoneRequired ? 'required' : 'nullable', 'string', 'regex:'.self::PHONE_PATTERN, 'different:phone'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'educational_level_id' => ['required', 'integer', Rule::in($levelIds)],
            'governorate' => [$site->askGovernorate ? 'required' : 'prohibited', 'nullable', 'string', Rule::in(array_keys((array) __('storefront::storefront.governorates')))],
            'school_type' => [$site->askSchoolType ? 'required' : 'prohibited', 'nullable', Rule::enum(SchoolType::class)],
        ], [
            'phone.regex' => __('storefront::storefront.errors.phone_format'),
            'parent_phone.regex' => __('storefront::storefront.errors.phone_format'),
        ], $this->attributes());

        try {
            $student = $register->handle([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'parent_phone' => $data['parent_phone'] ?? null,
                'password' => $data['password'],
                'educational_level_id' => (int) $data['educational_level_id'],
                'governorate' => $data['governorate'] ?? null,
                'school_type' => $data['school_type'] ?? null,
            ]);
        } catch (StudentsException $e) {
            throw ValidationException::withMessages(['phone' => $e->getMessage()]);
        }

        Auth::guard('student')->login($student);
        $request->session()->regenerate();

        return redirect()->route('site.dashboard')
            ->with('status', __('storefront::storefront.auth.welcome_new', ['name' => $student->name]));
    }

    public function forgot(): View
    {
        return view('storefront::pages.auth.forgot');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('student')->logout();

        // من غير invalidate(): الجلسة ممكن تكون شايلة دخول اللوحة كمان (حارس web)
        $request->session()->migrate(true);
        $request->session()->regenerateToken();

        return redirect()->route('site.home');
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        return collect(['name', 'phone', 'parent_phone', 'password', 'educational_level_id', 'governorate', 'school_type'])
            ->mapWithKeys(fn (string $field): array => [$field => __("storefront::storefront.fields.{$field}")])
            ->all();
    }

    public static function isSafePath(string $path): bool
    {
        return str_starts_with($path, '/') && ! str_starts_with($path, '//') && ! str_contains($path, '\\');
    }
}
