<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * الطلبة: تعديل البيانات، الحظر، الرصيد، الاشتراك اليدوي.
 */
final class StudentPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'students';
    }

    public function adjustWallet(User $user, Student $student): Response
    {
        return $this->owned($user, $student, 'adjust_wallet')
            ->rule(! $student->trashed(), 'record_trashed')
            ->response();
    }

    public function enroll(User $user, Student $student): Response
    {
        return $this->owned($user, $student, 'enroll')
            ->rule(! $student->trashed(), 'record_trashed')
            ->response();
    }

    public function resetPassword(User $user, Student $student): Response
    {
        return $this->owned($user, $student, 'reset_password')
            ->rule(! $student->trashed(), 'record_trashed')
            ->response();
    }

    /** مشاهدات زيادة لطالب في درس محدود المشاهدات */
    public function grantViews(User $user, Student $student): Response
    {
        return $this->owned($user, $student, 'grant_views')
            ->rule(! $student->trashed(), 'record_trashed')
            ->response();
    }

    public function block(User $user, Student $student): Response
    {
        return $this->owned($user, $student, 'block')
            ->rule(! $student->trashed(), 'record_trashed')
            ->response();
    }
}
