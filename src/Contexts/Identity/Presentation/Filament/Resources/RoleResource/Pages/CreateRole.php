<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Identity\Application\Actions\SyncRolePermissionsAction;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource;

final class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /** @param  array<string, mixed>  $data */
    protected function handleRecordCreation(array $data): Model
    {
        $permissions = SyncRolePermissionsAction::flatten($data['permission_groups'] ?? []);
        unset($data['permission_groups']);

        $data['guard_name'] = config('authorization.guard');

        /** @var Role $role */
        $role = self::getModel()::create($data);

        app(SyncRolePermissionsAction::class)->handle($role, $permissions);

        return $role;
    }
}
