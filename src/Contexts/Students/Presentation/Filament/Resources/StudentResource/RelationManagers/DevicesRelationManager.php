<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Application\Actions\ManageStudentAction;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * أجهزة الطالب («جهاز واحد لكل طالب») — المدرس بيشوفها ويفصلها كلها.
 * الظهور بصلاحية `manage_devices.students` (`StudentDevicePolicy::viewAny`).
 */
final class DevicesRelationManager extends RelationManager
{
    protected static string $relationship = 'devices';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('students::students.student.relations.devices');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->latest('last_seen_at'))
            ->columns([
                TextColumn::make('device_name')->label(__('students::students.device.fields.device'))->placeholder('—')
                    ->description(fn ($record): ?string => $record->user_agent !== null ? mb_strimwidth((string) $record->user_agent, 0, 70, '…') : null),
                TextColumn::make('ip_address')->label(__('students::students.device.fields.ip'))->placeholder('—'),
                TextColumn::make('last_seen_at')->label(__('students::students.device.fields.last_seen'))->dateTime('d M Y — H:i')->placeholder('—'),
                TextColumn::make('created_at')->label(__('students::students.device.fields.first_seen'))->dateTime('d M Y — H:i'),
            ])
            ->headerActions([
                Action::make('disconnect_devices')
                    ->label(__('students::students.student.actions.disconnect_devices'))
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('danger')
                    ->authorize(fn (): Response => Gate::inspect('manageDevices', $this->getOwnerRecord()))
                    ->requiresConfirmation()
                    ->modalDescription(__('students::students.student.confirm.disconnect_devices'))
                    ->action(function (): void {
                        $student = $this->getOwnerRecord();
                        $actor = auth()->user();

                        throw_unless($student instanceof Student && $actor instanceof User, AuthorizationException::class);

                        app(ManageStudentAction::class)->disconnectDevices($student, $actor);
                    })
                    ->successNotificationTitle(__('students::students.student.notifications.devices_disconnected')),
            ])
            ->emptyStateHeading(__('students::students.device.empty.heading'))
            ->emptyStateDescription(__('students::students.device.empty.description'));
    }
}
