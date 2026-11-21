<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Application\Actions\ForceLogoutAction;
use Src\Contexts\Identity\Domain\Models\UserDevice;

/**
 * «أجهزتي» — المستخدم بيشوف جلساته وبينهيها بنفسه (docs/12 بند ٢).
 */
final class MyDevices extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'identity::filament.pages.my-devices';

    public static function getSlug(?Panel $panel = null): string
    {
        return 'my-devices';
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.my_devices');
    }

    public function getTitle(): string
    {
        return __('identity::identity.device.plural');
    }

    public function getHeading(): string
    {
        return __('identity::identity.device.heading');
    }

    public function getSubheading(): string
    {
        return __('identity::identity.device.description');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => UserDevice::query()->where('user_id', auth()->id()))
            ->defaultSort('last_active_at', 'desc')
            // الجدول ده صغير وشخصي — التحميل المؤجّل هنا بيضر أكتر ما ينفع
            ->deferLoading(false)
            ->columns([
                TextColumn::make('device_name')
                    ->label(__('identity::identity.device.fields.device'))
                    ->weight(FontWeight::Medium)
                    ->description(fn (UserDevice $record): string => $record->platform.' · '.$record->browser),

                TextColumn::make('ip_address')
                    ->label(__('identity::identity.device.fields.ip'))
                    ->extraAttributes(['class' => 'fc-code']),

                IconColumn::make('session_id')
                    ->label(__('identity::identity.device.fields.current'))
                    ->boolean()
                    ->getStateUsing(fn (UserDevice $record): bool => $record->isCurrent())
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-minus-small')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('last_active_at')
                    ->label(__('identity::identity.device.fields.last_active'))
                    ->dateTime('d M Y — H:i')
                    ->since()
                    ->tooltip(fn (?object $state): ?string => $state?->format('Y-m-d H:i:s'))
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('terminate')
                    ->label(__('identity::identity.device.actions.terminate'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('identity::identity.device.confirm.terminate_heading'))
                    ->modalDescription(__('identity::identity.device.confirm.terminate_body'))
                    ->hidden(fn (UserDevice $record): bool => $record->isCurrent())
                    ->action(function (UserDevice $record): void {
                        app(ForceLogoutAction::class)->device(auth()->user(), $record);

                        Notification::make()
                            ->success()
                            ->title(__('identity::identity.device.notifications.terminated'))
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('terminate_others')
                    ->label(__('identity::identity.device.actions.terminate_others'))
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        app(ForceLogoutAction::class)->handle(
                            auth()->user(),
                            exceptSessionId: session()->getId(),
                        );

                        Notification::make()
                            ->success()
                            ->title(__('identity::identity.device.notifications.others_terminated'))
                            ->send();
                    }),
            ])
            ->emptyStateHeading(__('identity::identity.device.empty.heading'))
            ->emptyStateDescription(__('identity::identity.device.empty.description'))
            ->emptyStateIcon('heroicon-o-device-phone-mobile');
    }
}
