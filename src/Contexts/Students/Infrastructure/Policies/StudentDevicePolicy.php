<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Domain\Models\StudentDevice;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Domain\Authorization\Policy;

/**
 * أجهزة الطلبة — بتتعرض وتتفصل من شاشة الطالب بصلاحية `manage_devices.students`.
 * الأجهزة بتتسجّل من الموقع وقت الدخول بس — مفيش إضافة ولا تعديل من اللوحة.
 */
final class StudentDevicePolicy extends Policy
{
    protected function resource(): string
    {
        return 'students';
    }

    public function viewAny(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'manage_devices')->response();
    }

    public function view(User $user, StudentDevice $device): Response
    {
        return $this->owned($user, $device);
    }

    public function delete(User $user, StudentDevice $device): Response
    {
        return $this->owned($user, $device);
    }

    private function owned(User $user, StudentDevice $device): Response
    {
        return $this->decide()
            ->permission($user, $this, 'manage_devices')
            ->ruleOrNotFound(
                (int) $device->tenant_id === (int) app(TenantContext::class)->id(),
                'record_not_found',
            )
            ->response();
    }
}
