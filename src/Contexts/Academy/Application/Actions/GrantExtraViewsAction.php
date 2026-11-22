<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Application\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Academy\Domain\Exceptions\AcademyException;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Academy\Domain\Models\StudentItemViewGrant;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * استثناء لطالب واحد: مشاهدات زيادة في درس محدود المشاهدات.
 * المنحة بتتجمّع (منحتين ٢ + ٣ = ٥ زيادة)، و`ItemAccess` بيزوّدها على حد العنصر.
 */
final readonly class GrantExtraViewsAction
{
    public const MAX_PER_GRANT = 100;

    public function handle(Student $student, BootcampSectionItem $item, int $extraViews, User $actor): StudentItemViewGrant
    {
        Gate::forUser($actor)->authorize('grantViews', $student);

        throw_if($item->online_lesson_id === null || $item->allowed_views_number === null, AcademyException::because('views_not_limited'));
        throw_if($extraViews < 1 || $extraViews > self::MAX_PER_GRANT, AcademyException::because('extra_views_range', ['max' => self::MAX_PER_GRANT]));

        try {
            return $this->add($student, $item, $extraViews, $actor);
        } catch (UniqueConstraintViolationException) {
            // منحتين لنفس الطالب في نفس اللحظة لأول مرة — التانية بتلاقي الصف
            return $this->add($student, $item, $extraViews, $actor);
        }
    }

    public function revoke(StudentItemViewGrant $grant, User $actor): void
    {
        $student = $grant->student;
        throw_if($student === null, AcademyException::because('item_unavailable'));

        Gate::forUser($actor)->authorize('grantViews', $student);

        $grant->delete();
    }

    private function add(Student $student, BootcampSectionItem $item, int $extraViews, User $actor): StudentItemViewGrant
    {
        return DB::transaction(function () use ($student, $item, $extraViews, $actor): StudentItemViewGrant {
            $grant = StudentItemViewGrant::query()
                ->where('student_id', $student->getKey())
                ->where('bootcamp_section_item_id', $item->getKey())
                ->lockForUpdate()
                ->first();

            if ($grant === null) {
                return StudentItemViewGrant::query()->create([
                    'student_id' => $student->getKey(),
                    'bootcamp_section_item_id' => $item->getKey(),
                    'extra_views' => $extraViews,
                    'granted_by' => $actor->getKey(),
                ]);
            }

            $grant->forceFill([
                'extra_views' => $grant->extra_views + $extraViews,
                'granted_by' => $actor->getKey(),
            ])->save();

            return $grant;
        });
    }
}
