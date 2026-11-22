<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\Actions;

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Application\DataObjects\BulkEnrollmentResult;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\Enrollment;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Application\Actions\RegisterStudentAction;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * المدرس بيلصق أرقام موبايلات (رقم في كل سطر، بأي شكل مصري) ويفعّل كورس للكل
 * — مجانًا أو بالخصم من المحفظة — أو يلغي اشتراكهم. كل رقم بيتعالج لوحده:
 * رقم غلط مايوقفش الباقي، والنتيجة بتقول مين اتعمل له إيه.
 */
final readonly class BulkEnrollmentAction
{
    public const MAX_PHONES = 500;

    public function __construct(
        private EnrollStudentAction $enroll,
        private CheckoutAction $checkout,
        private BootcampAccess $access,
    ) {}

    public function activate(Bootcamp $bootcamp, string $phones, bool $chargeWallet, User $actor): BulkEnrollmentResult
    {
        Gate::forUser($actor)->authorize('create', Enrollment::class);

        [$students, $notFound] = $this->resolve($phones);
        $done = $skipped = $insufficient = [];
        $failed = [];

        foreach ($students as $phone => $student) {
            $this->access->forget();

            if ($this->access->has($student, $bootcamp)) {
                $skipped[] = $phone;

                continue;
            }

            try {
                if ($chargeWallet) {
                    // نفس مسار شراء الطالب بنفسه: فاتورة محفظة + قيد في السجل + فتح الكورس
                    $this->checkout->handle($student, $bootcamp, PaymentGateway::Wallet);
                } else {
                    $this->enroll->handle($student, $bootcamp, EnrollmentSource::Admin, enrolledBy: (int) $actor->getKey());
                }

                $done[] = $phone;
            } catch (BillingException $e) {
                match ($e->reason) {
                    'insufficient_balance' => $insufficient[] = $phone,
                    'already_enrolled' => $skipped[] = $phone,
                    default => $failed[$phone] = $e->getMessage(),
                };
            }
        }

        return new BulkEnrollmentResult($done, $skipped, $notFound, $insufficient, $failed);
    }

    public function cancel(Bootcamp $bootcamp, string $phones, User $actor): BulkEnrollmentResult
    {
        Gate::forUser($actor)->authorize('create', Enrollment::class);

        [$students, $notFound] = $this->resolve($phones);
        $done = $skipped = [];

        foreach ($students as $phone => $student) {
            $this->access->forget();

            if (! $this->access->has($student, $bootcamp)) {
                $skipped[] = $phone;

                continue;
            }

            $this->enroll->revoke($student, $bootcamp, (int) $actor->getKey());
            $done[] = $phone;
        }

        return new BulkEnrollmentResult($done, $skipped, $notFound);
    }

    /**
     * الأرقام بعد التطبيع ← الطالب، والأرقام اللي مالهاش طالب.
     *
     * @return array{0: array<string, Student>, 1: list<string>}
     */
    private function resolve(string $phones): array
    {
        $normalized = self::parse($phones);
        throw_if(count($normalized) > self::MAX_PHONES, BillingException::because('too_many_phones', ['max' => self::MAX_PHONES]));

        $found = Student::query()->whereIn('phone', $normalized)->get()->keyBy('phone');
        $students = [];
        $notFound = [];

        foreach ($normalized as $phone) {
            $student = $found->get($phone);

            if ($student instanceof Student) {
                $students[$phone] = $student;
            } else {
                $notFound[] = $phone;
            }
        }

        return [$students, $notFound];
    }

    /** @return list<string> أرقام مطبّعة من غير تكرار — سطر أو فاصلة بين كل رقم والتاني */
    public static function parse(string $phones): array
    {
        $lines = preg_split('/[\r\n,;،]+/u', $phones) ?: [];
        $normalized = array_map(static fn (string $line): string => RegisterStudentAction::normalizePhone($line), $lines);

        return array_values(array_unique(array_filter($normalized, static fn (string $phone): bool => $phone !== '')));
    }
}
