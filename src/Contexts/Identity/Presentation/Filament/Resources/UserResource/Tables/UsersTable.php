<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Resources\UserResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Src\Contexts\Identity\Application\Actions\BulkUpdateUserStatusAction;
use Src\Contexts\Identity\Application\Actions\ForceLogoutAction;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Infrastructure\Exports\UserExporter;
use STS\FilamentImpersonate\Actions\Impersonate;

final class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->defaultSort('created_at', 'desc')
            ->filters(self::filters())
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions(self::recordActions())
            ->toolbarActions(self::toolbarActions())
            ->headerActions([
                CreateAction::make()->label(__('identity::identity.user.actions.create')),
            ])
            ->emptyStateHeading(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results')
                : __('identity::identity.user.empty.heading'))
            ->emptyStateDescription(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results_description')
                : __('identity::identity.user.empty.description'))
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateActions([
                CreateAction::make()->label(__('identity::identity.user.empty.cta')),
            ]);
    }

    /** @return array<int, mixed> */
    private static function columns(): array
    {
        return [
            TextColumn::make('name')
                ->label(__('identity::identity.user.fields.name'))
                ->searchable()
                ->sortable()
                ->weight(FontWeight::Medium)
                ->description(fn (User $record): string => $record->email)
                ->wrap(),

            TextColumn::make('code')
                ->label(__('identity::identity.user.fields.code'))
                ->searchable(isIndividual: true)
                ->copyable()
                ->copyMessage(__('common.copied'))
                // خط mono + عزل ثنائي الاتجاه — عشان الكود ما ينقلبش في نص عربي
                ->extraAttributes(['class' => 'fc-code'])
                ->toggleable(),

            TextColumn::make('roles.name')
                ->label(__('identity::identity.user.fields.roles'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => __("authorization.roles.{$state}"))
                ->separator('،')
                ->toggleable(),

            TextColumn::make('status')
                ->label(__('common.status'))
                ->badge()
                ->sortable()
                ->toggleable(),

            TextColumn::make('last_login_at')
                ->label(__('identity::identity.user.fields.last_login'))
                ->dateTime('d M Y — H:i')
                ->since()
                ->tooltip(fn (?object $state): ?string => $state?->format('Y-m-d H:i:s'))
                ->placeholder('—')
                ->sortable()
                ->toggleable(),

            TextColumn::make('created_at')
                ->label(__('common.created_at'))
                ->dateTime('d M Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /** @return array<int, mixed> */
    private static function filters(): array
    {
        return [
            SelectFilter::make('status')
                ->label(__('common.status'))
                ->options(UserStatus::class)
                ->multiple(),

            SelectFilter::make('roles')
                ->label(__('identity::identity.user.fields.roles'))
                ->relationship('roles', 'name')
                ->getOptionLabelFromRecordUsing(fn (Role $record): string => __("authorization.roles.{$record->name}"))
                ->multiple()
                ->preload()
                ->searchable(),

            TernaryFilter::make('is_active')->label(__('common.active')),

            Filter::make('created_at')
                ->label(__('common.created_between'))
                ->schema([
                    DatePicker::make('from')->label(__('common.from')),
                    DatePicker::make('until')->label(__('common.until')),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date)))
                // إلزامي: من غيرها المستخدم بيفلتر وينسى وبعدين يقول «البيانات ناقصة»
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if (filled($data['from'] ?? null)) {
                        $indicators[] = Indicator::make(__('common.from').': '.$data['from'])->removeField('from');
                    }

                    if (filled($data['until'] ?? null)) {
                        $indicators[] = Indicator::make(__('common.until').': '.$data['until'])->removeField('until');
                    }

                    return $indicators;
                }),

            TrashedFilter::make(),
        ];
    }

    /** @return array<int, mixed> */
    private static function recordActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make(),
                EditAction::make(),

                Impersonate::make()
                    ->label(__('identity::identity.user.actions.impersonate'))
                    ->icon('heroicon-o-user-circle')
                    // السجل بيتبعت للـ Policy تلقائياً — متمرّرهوش بإيدك
                    ->authorize('impersonate')
                    ->authorizationTooltip()
                    ->requiresConfirmation()
                    ->modalHeading(__('identity::identity.user.confirm.impersonate_heading'))
                    ->modalDescription(__('identity::identity.user.confirm.impersonate_body')),

                Action::make('force_logout')
                    ->label(__('identity::identity.user.actions.force_logout'))
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->color('warning')
                    ->authorize('forceLogout')
                    ->authorizationTooltip()
                    ->requiresConfirmation()
                    ->modalHeading(__('identity::identity.user.confirm.force_logout_heading'))
                    ->modalDescription(__('identity::identity.user.confirm.force_logout_body'))
                    ->action(fn (User $record) => app(ForceLogoutAction::class)->handle($record))
                    ->successNotificationTitle(__('identity::identity.user.notifications.logged_out')),

                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
                ->label(__('common.actions'))
                ->icon('heroicon-m-ellipsis-vertical')
                ->button()
                ->color('gray'),
        ];
    }

    /** @return array<int, mixed> */
    private static function toolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                // فحص كل سجل بالـ Policy — اللي يرسب بيتستبعد قبل التنفيذ
                DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                RestoreBulkAction::make()->authorizeIndividualRecords('restore'),

                BulkAction::make('activate')
                    ->label(__('identity::identity.user.actions.bulk_activate'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorizeIndividualRecords('update')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (Collection $records) => app(BulkUpdateUserStatusAction::class)
                        ->handle($records, UserStatus::Active)),

                BulkAction::make('suspend')
                    ->label(__('identity::identity.user.actions.bulk_suspend'))
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->authorizeIndividualRecords('update')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (Collection $records) => app(BulkUpdateUserStatusAction::class)
                        ->handle($records, UserStatus::Suspended)),

                // التصدير في الطابور — فحص على مستوى المورد مش لكل سجل
                ExportBulkAction::make()
                    ->exporter(UserExporter::class)
                    ->authorize('export'),
            ]),
        ];
    }
}
