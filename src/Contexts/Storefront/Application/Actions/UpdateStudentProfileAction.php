<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Application\Actions;

use Illuminate\Support\Facades\Hash;
use Src\Contexts\Storefront\Domain\Exceptions\StorefrontException;
use Src\Contexts\Students\Application\Actions\RegisterStudentAction;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * الطالب بيعدّل بياناته من «حسابي». الموبايل (اسم الدخول) والرصيد والحالة
 * مش من الحقول المسموحة — قايمة بيضا صريحة.
 */
final readonly class UpdateStudentProfileAction
{
    /** @param array{name: string, parent_phone?: string|null, educational_level_id?: int|null} $data */
    public function handle(Student $student, array $data): Student
    {
        $student->forceFill([
            'name' => trim($data['name']),
            'parent_phone' => filled($data['parent_phone'] ?? null)
                ? RegisterStudentAction::normalizePhone((string) $data['parent_phone'])
                : null,
            'educational_level_id' => $data['educational_level_id'] ?? $student->educational_level_id,
        ])->save();

        return $student;
    }

    public function changePassword(Student $student, string $current, string $new): void
    {
        throw_unless(Hash::check($current, (string) $student->getAuthPassword()), StorefrontException::because('wrong_password'));

        $student->forceFill(['password' => $new])->save();
    }
}
