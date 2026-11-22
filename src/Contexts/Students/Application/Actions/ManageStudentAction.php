<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Application\Actions;

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Application\Actions\EnrollStudentAction;
use Src\Contexts\Billing\Application\Actions\WalletAction;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Enums\TransactionType;
use Src\Contexts\Billing\Domain\Models\Enrollment;
use Src\Contexts\Billing\Domain\Models\MoneyTransaction;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Domain\Enums\StudentStatus;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * أزرار شاشة الطالب في اللوحة — كل زرار = دالة هنا، والـ Gate جواها (الحاجز الأخير).
 */
final readonly class ManageStudentAction
{
    public function __construct(
        private EnrollStudentAction $enroll,
        private WalletAction $wallet,
    ) {}

    public function enroll(Student $student, Bootcamp $bootcamp, User $actor): Enrollment
    {
        Gate::forUser($actor)->authorize('enroll', $student);

        return $this->enroll->handle($student, $bootcamp, EnrollmentSource::Admin, enrolledBy: (int) $actor->getKey());
    }

    public function unenroll(Student $student, Bootcamp $bootcamp, User $actor): Enrollment
    {
        Gate::forUser($actor)->authorize('enroll', $student);

        return $this->enroll->revoke($student, $bootcamp, (int) $actor->getKey());
    }

    public function adjustWallet(Student $student, TransactionType $type, float $amount, ?string $notes, User $actor): MoneyTransaction
    {
        Gate::forUser($actor)->authorize('adjustWallet', $student);

        return $type === TransactionType::Deposit
            ? $this->wallet->deposit($student, $amount, TransactionSource::Admin, notes: $notes, actorId: (int) $actor->getKey())
            : $this->wallet->withdraw($student, $amount, TransactionSource::Admin, notes: $notes, actorId: (int) $actor->getKey());
    }

    public function resetPassword(Student $student, string $password, User $actor): Student
    {
        Gate::forUser($actor)->authorize('resetPassword', $student);

        // تغيير الـ hash بيقفل أي جلسة مفتوحة للطالب (AuthenticateSession على مسارات الطالب)
        $student->forceFill(['password' => $password, 'remember_token' => null])->save();

        return $student;
    }

    /**
     * «افصل كل الأجهزة»: الطالب بيخرج من كل مكان في أول طلب (`AuthenticateStudent`)،
     * وأول جهاز يدخل منه بعدها بياخد المكان. `remember_token` بيتغيّر عشان
     * «افتكرني» القديم مايرجّعوش من غير كلمة السر.
     */
    public function disconnectDevices(Student $student, User $actor): int
    {
        Gate::forUser($actor)->authorize('manageDevices', $student);

        $removed = $student->devices()->delete();
        $student->forceFill(['remember_token' => null])->saveQuietly();

        activity('students')
            ->performedOn($student)
            ->causedBy($actor)
            ->withProperties(['devices' => $removed])
            ->log('devices_disconnected');

        return (int) $removed;
    }

    public function toggleBlock(Student $student, User $actor): Student
    {
        Gate::forUser($actor)->authorize('block', $student);

        $student->update([
            'status' => $student->isBlocked() ? StudentStatus::Active : StudentStatus::Blocked,
        ]);

        return $student;
    }
}
