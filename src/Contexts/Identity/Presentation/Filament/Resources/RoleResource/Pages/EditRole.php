<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Identity\Application\Actions\SyncRolePermissionsAction;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource;
use Src\Support\Infrastructure\Authorization\PermissionBuilder;

final class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $role */
        $role = $this->getRecord();

        $assigned = $role->permissions->pluck('name')->all();

        foreach (app(PermissionBuilder::class)->groups() as $group => $resources) {
            $groupPermissions = [];

            foreach ($resources as $permissions) {
                $groupPermissions = [...$groupPermissions, ...$permissions];
            }

            $data['permission_groups'][$group] = array_values(array_intersect($assigned, $groupPermissions));
        }

        return $data;
    }

    /**
     * @param Role $record
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $permissions = SyncRolePermissionsAction::flatten($data['permission_groups'] ?? []);
        unset($data['permission_groups']);

        $record->update($data);

        app(SyncRolePermissionsAction::class)->handle($record, $permissions);

        return $record;
    }
}
