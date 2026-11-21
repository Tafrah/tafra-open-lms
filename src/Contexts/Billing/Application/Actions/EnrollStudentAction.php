<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\Actions;

use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Billing\Domain\Enums\EnrollmentType;
use Src\Contexts\Billing\Domain\Models\Enrollment;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\RechargeCard;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * يفتح الكورس للطالب. مدة المشاهدة من `can_watch_in_days` (لو محددة).
 * لو مشترك أصلًا ومفيش مدة، مابنكررش السجل.
 */
final readonly class EnrollStudentAction
{
    public function __construct(private BootcampAccess $access) {}

    public function handle(
        Student $student,
        Bootcamp $bootcamp,
        EnrollmentSource $source,
        ?PaymentInvoice $invoice = null,
        ?RechargeCard $card = null,
        ?int $enrolledBy = null,
    ): Enrollment {
        $current = $this->access->current($student, $bootcamp);
        $days = $bootcamp->can_watch_in_days;

        if ($current !== null && $current->isActive() && $days === null) {
            return $current;
        }

        $enrollment = Enrollment::query()->create([
            'bootcamp_id' => $bootcamp->getKey(),
            'student_id' => $student->getKey(),
            'type' => EnrollmentType::Enroll,
            'source' => $source,
            'payment_invoice_id' => $invoice?->getKey(),
            'recharge_card_id' => $card?->getKey(),
            'enrolled_by' => $enrolledBy,
            'access_duration_days' => $days,
            'expires_at' => $days !== null ? now()->addDays($days) : null,
        ]);

        $this->access->forget();

        return $enrollment;
    }

    public function revoke(Student $student, Bootcamp $bootcamp, ?int $actorId = null): Enrollment
    {
        $enrollment = Enrollment::query()->create([
            'bootcamp_id' => $bootcamp->getKey(),
            'student_id' => $student->getKey(),
            'type' => EnrollmentType::Unenroll,
            'source' => EnrollmentSource::Admin,
            'enrolled_by' => $actorId,
        ]);

        $this->access->forget();

        return $enrollment;
    }
}
