<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\Queries;

use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Domain\Enums\EnrollmentType;
use Src\Contexts\Billing\Domain\Models\Enrollment;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * هل الطالب ده مشترك في الكورس دلوقتي؟ آخر سجل في التاريخ هو الحكم.
 */
final class BootcampAccess
{
    /** @var array<string, Enrollment|null> */
    private array $memo = [];

    public function current(Student $student, Bootcamp $bootcamp): ?Enrollment
    {
        $key = $student->getKey().':'.$bootcamp->getKey();

        if (! array_key_exists($key, $this->memo)) {
            $this->memo[$key] = Enrollment::query()
                ->where('student_id', $student->getKey())
                ->where('bootcamp_id', $bootcamp->getKey())
                ->latest('id')
                ->first();
        }

        return $this->memo[$key];
    }

    public function has(Student $student, Bootcamp $bootcamp): bool
    {
        return $this->current($student, $bootcamp)?->isActive() ?? false;
    }

    /** @return list<int> كل الكورسات اللي الطالب مشترك فيها دلوقتي */
    public function bootcampIds(Student $student): array
    {
        $latest = Enrollment::query()
            ->where('student_id', $student->getKey())
            ->orderBy('id')
            ->get(['id', 'bootcamp_id', 'type', 'expires_at'])
            ->keyBy('bootcamp_id');

        return $latest
            ->filter(fn (Enrollment $e): bool => $e->type === EnrollmentType::Enroll
                && ($e->expires_at === null || $e->expires_at->isFuture()))
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    public function forget(): void
    {
        $this->memo = [];
    }
}
