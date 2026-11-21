<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Application\Actions;

use Illuminate\Support\Facades\Hash;
use Src\Contexts\Storefront\Domain\Exceptions\StorefrontException;
use Src\Contexts\Students\Application\Actions\RegisterStudentAction;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * دخول الطالب بالموبايل وكلمة السر. نفس الرسالة للرقم الغلط وكلمة السر
 * الغلط (مفيش تسريب لوجود الرقم)، والحساب الموقوف بيترفض حتى بكلمة سر صح.
 */
final readonly class AuthenticateStudentAction
{
    public function handle(string $phone, string $password): Student
    {
        $student = Student::query()
            ->where('phone', RegisterStudentAction::normalizePhone($phone))
            ->first();

        if ($student === null) {
            // نفس تكلفة الوقت تقريبًا — مايبانش من التوقيت إن الرقم مش موجود
            Hash::make($password);

            throw StorefrontException::because('invalid_credentials');
        }

        throw_unless(Hash::check($password, (string) $student->getAuthPassword()), StorefrontException::because('invalid_credentials'));
        throw_if($student->isBlocked(), StorefrontException::because('blocked'));

        $student->forceFill(['last_login_at' => now()])->saveQuietly();

        return $student;
    }
}
