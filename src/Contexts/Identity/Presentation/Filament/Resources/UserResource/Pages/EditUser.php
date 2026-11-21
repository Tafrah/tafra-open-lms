<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Resources\UserResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Identity\Application\Actions\SyncUserRolesAction;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    /**
     * @param User $record
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $roles = $data['roles'] ?? null;
        $status = $data['status'] ?? null;
        $isActive = $data['is_active'] ?? null;
        unset($data['roles'], $data['status'], $data['is_active']);

        $record->update($data);

        // حقول الحالة بتتحط برمجياً — لو `saved()` منعها مش هتكون في $data
        $state = array_filter(
            ['status' => $status, 'is_active' => $isActive],
            static fn (mixed $value): bool => $value !== null,
        );

        if ($state !== []) {
            $record->forceFill($state)->save();
        }

        if (is_array($roles)) {
            app(SyncUserRolesAction::class)->handle($record, $roles);
        }

        return $record;
    }
}
