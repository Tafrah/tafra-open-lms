<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Application\Actions\ActivateTenantAction;
use Src\Contexts\Tenancy\Application\Actions\ImpersonateTenantAction;
use Src\Contexts\Tenancy\Application\Actions\SuspendTenantAction;
use Src\Contexts\Tenancy\Domain\Models\Tenant;

final class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('tenancy::tenancy.tenant.fields.name'))
                    ->weight(FontWeight::Medium)
                    ->description(fn (Tenant $record): string => $record->slug)
                    ->searchable(['slug'])
                    ->sortable(),

                TextColumn::make('domain')
                    ->label(__('tenancy::tenancy.tenant.fields.domain'))
                    ->extraAttributes(['class' => 'fc-code'])
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('users_count')
                    ->counts('users')
                    ->label(__('tenancy::tenancy.tenant.fields.users_count'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (int $state): string => fc_number($state)),

                TextColumn::make('lifecycle')
                    ->label(__('common.status'))
                    ->state(fn (Tenant $record): string => match (true) {
                        $record->isSuspended() => 'suspended',
                        $record->is_active => 'active',
                        default => 'inactive',
                    })
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("tenancy::tenancy.tenant.status.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'warning',
                        default => 'danger',
                    })
                    ->tooltip(fn (Tenant $record): ?string => $record->suspension_reason),

                TextColumn::make('provisioning_status')
                    ->label(__('tenancy::tenancy.tenant.fields.provisioning_status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("tenancy::tenancy.tenant.provisioning.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        Tenant::PROVISIONING_READY => 'success',
                        Tenant::PROVISIONING_FAILED => 'danger',
                        default => 'warning',
                    })
                    ->toggleable(),

                TextColumn::make('trial_ends_at')
                    ->label(__('tenancy::tenancy.tenant.fields.trial_ends_at'))
                    ->dateTime('d M Y')
                    ->since()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')->label(__('common.active')),
                TernaryFilter::make('suspended_at')
                    ->label(__('tenancy::tenancy.tenant.status.suspended'))
                    ->nullable(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    self::impersonateAction(),
                    EditAction::make()->authorizationTooltip(),
                    self::suspendAction(),
                    self::activateAction(),
                    DeleteAction::make()->authorizationTooltip(),
                    RestoreAction::make(),
                    self::forceDeleteAction(),
                ])
                    ->label(__('common.actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                ]),
            ])
            ->emptyStateHeading(__('tenancy::tenancy.tenant.empty.heading'))
            ->emptyStateDescription(__('tenancy::tenancy.tenant.empty.description'))
            ->emptyStateIcon('heroicon-o-building-office-2')
            ->emptyStateActions([CreateAction::make()]);
    }

    private static function impersonateAction(): Action
    {
        return Action::make('impersonate')
            ->label(__('tenancy::tenancy.tenant.actions.impersonate'))
            ->icon('heroicon-o-arrow-right-end-on-rectangle')
            // السجل بيتبعت للـ Policy تلقائياً — متمرّرهوش بإيدك
            ->authorize('impersonate')
            ->authorizationTooltip()
            ->requiresConfirmation()
            ->modalHeading(__('tenancy::tenancy.tenant.confirm.impersonate_heading'))
            ->modalDescription(__('tenancy::tenancy.tenant.confirm.impersonate_body'))
            // نفس طريقة Action الباكدج: `redirect()` بتاع Livewire مش `return redirect()`
            ->action(function (Tenant $record, Action $action): void {
                app(ImpersonateTenantAction::class)->handle($record, self::actor());

                $action->redirect(Filament::getPanel('admin')->getUrl($record));
            });
    }

    private static function suspendAction(): Action
    {
        return Action::make('suspend')
            ->label(__('tenancy::tenancy.tenant.actions.suspend'))
            ->icon('heroicon-o-pause-circle')
            ->color('warning')
            ->visible(fn (Tenant $record): bool => ! $record->isSuspended())
            ->authorize('suspend')
            ->authorizationTooltip()
            ->modalHeading(__('tenancy::tenancy.tenant.confirm.suspend_heading'))
            ->modalDescription(__('tenancy::tenancy.tenant.confirm.suspend_body'))
            ->schema([
                Textarea::make('reason')
                    ->label(__('tenancy::tenancy.tenant.fields.suspension_reason'))
                    ->helperText(__('tenancy::tenancy.tenant.help.suspension_reason'))
                    ->required()
                    ->maxLength(255),
            ])
            ->action(fn (Tenant $record, array $data) => app(SuspendTenantAction::class)
                ->handle($record, (string) $data['reason'], self::actor()))
            ->successNotificationTitle(__('tenancy::tenancy.tenant.notifications.suspended'));
    }

    private static function activateAction(): Action
    {
        return Action::make('activate')
            ->label(__('tenancy::tenancy.tenant.actions.activate'))
            ->icon('heroicon-o-play-circle')
            ->color('success')
            ->visible(fn (Tenant $record): bool => $record->isSuspended())
            ->authorize('activate')
            ->authorizationTooltip()
            ->requiresConfirmation()
            ->modalHeading(__('tenancy::tenancy.tenant.confirm.activate_heading'))
            ->modalDescription(__('tenancy::tenancy.tenant.confirm.activate_body'))
            ->action(fn (Tenant $record) => app(ActivateTenantAction::class)->handle($record, self::actor()))
            ->successNotificationTitle(__('tenancy::tenancy.tenant.notifications.activated'));
    }

    /**
     * الحذف النهائي بيمسح قاعدة المؤسسة في وضع multi — فالتأكيد بكتابة
     * المُعرّف مش زرار «متأكد؟» (docs/24 بند ٣).
     */
    private static function forceDeleteAction(): ForceDeleteAction
    {
        return ForceDeleteAction::make()
            ->authorizationTooltip()
            ->modalDescription(__('tenancy::tenancy.tenant.confirm.force_delete_body'))
            ->schema([
                TextInput::make('confirmation')
                    ->label(fn (Tenant $record): string => __('tenancy::tenancy.tenant.actions.force_delete_confirm', ['slug' => $record->slug]))
                    ->required()
                    ->in(fn (Tenant $record): array => [$record->slug])
                    ->validationMessages(['in' => __('tenancy::tenancy.tenant.validation.slug_mismatch')]),
            ]);
    }

    private static function actor(): User
    {
        $actor = auth()->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
