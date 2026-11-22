<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Billing\Domain\Enums\TransactionType;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Application\Actions\ManageStudentAction;
use Src\Contexts\Students\Domain\Enums\StudentStatus;
use Src\Contexts\Students\Domain\Models\Student;

final class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('students::students.student.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->description(fn (Student $record): string => $record->phone),
                TextColumn::make('code')
                    ->label(__('students::students.student.fields.code'))
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('level.name')
                    ->label(__('students::students.student.fields.level'))
                    ->placeholder('—'),
                TextColumn::make('balance')
                    ->label(__('students::students.student.fields.balance'))
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('common.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('educational_level_id')
                    ->label(__('students::students.student.fields.level'))
                    ->relationship('level', 'name'),
                SelectFilter::make('status')
                    ->label(__('common.status'))
                    ->options(StudentStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->authorizationTooltip(),
                    Action::make('enroll')
                        ->label(__('students::students.student.actions.enroll'))
                        ->icon('heroicon-o-academic-cap')
                        ->color('success')
                        ->authorize('enroll')
                        ->schema([
                            Select::make('bootcamp_id')
                                ->label(__('students::students.student.fields.bootcamp'))
                                ->options(fn (): array => Bootcamp::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(fn (Student $record, array $data) => app(ManageStudentAction::class)
                            ->enroll($record, Bootcamp::query()->findOrFail($data['bootcamp_id']), self::actor()))
                        ->successNotificationTitle(__('students::students.student.notifications.enrolled')),
                    Action::make('unenroll')
                        ->label(__('students::students.student.actions.unenroll'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->authorize('enroll')
                        ->schema([
                            Select::make('bootcamp_id')
                                ->label(__('students::students.student.fields.bootcamp'))
                                ->options(fn (Student $record): array => Bootcamp::query()
                                    ->whereIn('id', app(BootcampAccess::class)->bootcampIds($record))
                                    ->pluck('name', 'id')
                                    ->all())
                                ->required(),
                        ])
                        ->requiresConfirmation()
                        ->action(fn (Student $record, array $data) => app(ManageStudentAction::class)
                            ->unenroll($record, Bootcamp::query()->findOrFail($data['bootcamp_id']), self::actor()))
                        ->successNotificationTitle(__('students::students.student.notifications.unenrolled')),
                    Action::make('wallet')
                        ->label(__('students::students.student.actions.wallet'))
                        ->icon('heroicon-o-wallet')
                        ->color('info')
                        ->authorize('adjustWallet')
                        ->schema([
                            Select::make('type')
                                ->label(__('students::students.student.fields.transaction_type'))
                                ->options(TransactionType::class)
                                ->default(TransactionType::Deposit)
                                ->native(false)
                                ->required(),
                            TextInput::make('amount')
                                ->label(__('students::students.student.fields.amount'))
                                ->numeric()
                                ->minValue(1)
                                ->required(),
                            TextInput::make('notes')
                                ->label(__('students::students.student.fields.notes'))
                                ->maxLength(255),
                        ])
                        ->action(fn (Student $record, array $data) => app(ManageStudentAction::class)->adjustWallet(
                            $record,
                            $data['type'] instanceof TransactionType ? $data['type'] : TransactionType::from((string) $data['type']),
                            (float) $data['amount'],
                            $data['notes'] ?? null,
                            self::actor(),
                        ))
                        ->successNotificationTitle(__('students::students.student.notifications.wallet')),
                    Action::make('reset_password')
                        ->label(__('students::students.student.actions.reset_password'))
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->authorize('resetPassword')
                        ->schema([
                            TextInput::make('password')
                                ->label(__('students::students.student.fields.new_password'))
                                ->password()
                                ->revealable()
                                ->minLength(8)
                                ->required(),
                        ])
                        ->action(fn (Student $record, array $data) => app(ManageStudentAction::class)->resetPassword($record, (string) $data['password'], self::actor()))
                        ->successNotificationTitle(__('students::students.student.notifications.password')),
                    Action::make('block')
                        ->label(fn (Student $record): string => $record->isBlocked()
                            ? __('students::students.student.actions.unblock')
                            : __('students::students.student.actions.block'))
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->authorize('block')
                        ->requiresConfirmation()
                        ->action(fn (Student $record) => app(ManageStudentAction::class)->toggleBlock($record, self::actor())),
                    Action::make('disconnect_devices')
                        ->label(__('students::students.student.actions.disconnect_devices'))
                        ->icon('heroicon-o-device-phone-mobile')
                        ->color('warning')
                        ->authorize('manageDevices')
                        ->requiresConfirmation()
                        ->modalDescription(__('students::students.student.confirm.disconnect_devices'))
                        ->action(fn (Student $record) => app(ManageStudentAction::class)->disconnectDevices($record, self::actor()))
                        ->successNotificationTitle(__('students::students.student.notifications.devices_disconnected')),
                    DeleteAction::make()->authorizationTooltip(),
                    RestoreAction::make(),
                ])
                    ->label(__('common.actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ])
            ->headerActions([CreateAction::make()])
            ->emptyStateHeading(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results')
                : __('students::students.student.empty.heading'))
            ->emptyStateDescription(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results_description')
                : __('students::students.student.empty.description'));
    }

    private static function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthorizationException::class);

        return $user;
    }
}
