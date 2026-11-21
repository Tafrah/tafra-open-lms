<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Storefront\Application\Actions\UpdateStudentProfileAction;
use Src\Contexts\Storefront\Application\Queries\CourseCatalog;
use Src\Contexts\Storefront\Application\Queries\StudentProgress;
use Src\Contexts\Storefront\Domain\Exceptions\StorefrontException;
use Src\Contexts\Storefront\Presentation\Http\Controllers\Concerns\ResolvesStudent;
use Src\Contexts\Students\Application\Actions\RegisterStudentAction;

/** لوحة الطالب: الرئيسية، كورساتي، فواتيري، امتحاناتي، حسابي. */
final class StudentAreaController
{
    use ResolvesStudent;

    public function __construct(private readonly StudentProgress $progress) {}

    public function dashboard(): View
    {
        $student = $this->student();

        return view('storefront::pages.student.dashboard', [
            'student' => $student,
            'courses' => $this->progress->courses($student)->take(6),
            'pendingInvoices' => PaymentInvoice::query()
                ->with('bootcamp:id,name,slug')
                ->where('student_id', $student->getKey())
                ->where('status', InvoiceStatus::Pending)
                ->latest('id')
                ->limit(5)
                ->get(),
            'attempts' => OnlinePassedExam::query()
                ->with('exam:id,name,min_percentage')
                ->where('student_id', $student->getKey())
                ->where('finished', true)
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }

    public function courses(): View
    {
        return view('storefront::pages.student.courses', [
            'courses' => $this->progress->courses($this->student()),
        ]);
    }

    public function invoices(): View
    {
        return view('storefront::pages.student.invoices', [
            'invoices' => PaymentInvoice::query()
                ->with(['bootcamp:id,name,slug', 'receipts:id,payment_invoice_id,status'])
                ->where('student_id', $this->student()->getKey())
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function exams(): View
    {
        return view('storefront::pages.student.exams', [
            'attempts' => OnlinePassedExam::query()
                ->with('exam:id,name,min_percentage')
                ->where('student_id', $this->student()->getKey())
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function profile(CourseCatalog $catalog): View
    {
        $student = $this->student();
        $student->loadMissing('level');

        return view('storefront::pages.student.profile', [
            'student' => $student,
            'levels' => $catalog->levels(),
            'stats' => [
                'courses' => count(app(BootcampAccess::class)->bootcampIds($student)),
                'exams' => OnlinePassedExam::query()->where('student_id', $student->getKey())->where('finished', true)->count(),
                'balance' => (float) $student->balance,
            ],
        ]);
    }

    public function updateProfile(Request $request, UpdateStudentProfileAction $update, CourseCatalog $catalog): RedirectResponse
    {
        if (filled($request->input('parent_phone'))) {
            $request->merge(['parent_phone' => RegisterStudentAction::normalizePhone((string) $request->input('parent_phone'))]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'parent_phone' => ['nullable', 'string', 'regex:'.AuthController::PHONE_PATTERN],
            'educational_level_id' => ['required', 'integer', Rule::in($catalog->levels()->modelKeys())],
        ], ['parent_phone.regex' => __('storefront::storefront.errors.phone_format')], [
            'name' => __('storefront::storefront.fields.name'),
            'parent_phone' => __('storefront::storefront.fields.parent_phone'),
            'educational_level_id' => __('storefront::storefront.fields.educational_level_id'),
        ]);

        $update->handle($this->student(), [
            'name' => (string) $data['name'],
            'parent_phone' => $data['parent_phone'] ?? null,
            'educational_level_id' => (int) $data['educational_level_id'],
        ]);

        return redirect()->route('site.profile')->with('status', __('storefront::storefront.profile.saved'));
    }

    public function updatePassword(Request $request, UpdateStudentProfileAction $update): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed', 'different:current_password'],
        ], [], [
            'current_password' => __('storefront::storefront.fields.current_password'),
            'password' => __('storefront::storefront.fields.new_password'),
        ]);

        try {
            $update->changePassword($this->student(), (string) $data['current_password'], (string) $data['password']);
        } catch (StorefrontException $e) {
            throw ValidationException::withMessages(['current_password' => $e->getMessage()])->errorBag('password');
        }

        $request->session()->regenerate();

        return redirect()->route('site.profile')->with('status', __('storefront::storefront.profile.password_saved'));
    }
}
