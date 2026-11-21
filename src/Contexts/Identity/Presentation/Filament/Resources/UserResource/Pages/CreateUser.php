<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Identity\Application\Actions\InviteUserAction;
use Src\Contexts\Identity\Application\Actions\SyncUserRolesAction;
use Src\Contexts\Identity\Application\DataObjects\InviteUserData;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource;
use Src\Support\Application\Contracts\TenantContext;

final class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * الإنشاء بيمرّ على `InviteUserAction` — هو اللي بيربط المؤسسة
     * ويطلق `UserInvited`، واللي عليه بتتبعت الدعوة. الصفحة بتضيف
     * بس الحاجات اللي الفورم مسؤول عنها: الأدوار وحقول الحالة
     * (والاتنين بيوصلوا لهنا لو `saved()` سمح بيهم بس).
     *
     * @param array<string, mixed> $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $tenantId = app(TenantContext::class)->id();

        $roles = $data['roles'] ?? null;
        $status = $data['status'] ?? null;
        $isActive = $data['is_active'] ?? null;

        /** @var User $user */
        $user = app(InviteUserAction::class)->handle(
            InviteUserData::fromArray($data, (int) $tenantId),
        );

        // حقول الحالة مش في `$fillable` عن قصد — بتتحط برمجياً بعد ما
        // `saved()` في الفورم يكون قرّر إن المستخدم مسموح له يكتبها.
        $state = array_filter(
            ['status' => $status, 'is_active' => $isActive],
            static fn (mixed $value): bool => $value !== null,
        );

        if ($state !== []) {
            $user->forceFill($state)->save();
        }

        if (is_array($roles) && $roles !== []) {
            app(SyncUserRolesAction::class)->handle($user, $roles, tenantId: $tenantId);
        }

        return $user;
    }
}
