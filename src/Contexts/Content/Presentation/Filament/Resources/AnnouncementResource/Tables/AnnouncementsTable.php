<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Toggle;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Src\Contexts\Content\Application\Actions\PublishAnnouncementAction;
use Src\Contexts\Content\Application\Actions\TogglePinAnnouncementAction;
use Src\Contexts\Content\Application\DataObjects\PublishAnnouncementData;
use Src\Contexts\Content\Domain\Enums\AnnouncementSeverity;
use Src\Contexts\Content\Domain\Enums\AnnouncementStatus;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Content\Infrastructure\Exports\AnnouncementExporter;

final class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_pinned')
                    // تسمية حقيقية مطلوبة: الأعمدة قابلة لإعادة الترتيب،
                    // وقارئ الشاشة محتاجها كمان
                    ->label(__('content::content.announcement.fields.pinned'))
                    ->alignCenter()
                    ->boolean()
                    ->trueIcon('heroicon-s-bookmark')
                    ->falseIcon('')
                    ->trueColor('warning'),

                TextColumn::make('title')
                    ->label(__('content::content.announcement.fields.title'))
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->wrap()
                    ->limit(80),

                TextColumn::make('status')
                    ->label(__('common.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('severity')
                    ->label(__('content::content.announcement.fields.severity'))
                    ->badge()
                    ->toggleable(),

                TextColumn::make('author.name')
                    ->label(__('content::content.announcement.fields.author'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('published_at')
                    ->label(__('content::content.announcement.fields.published_at'))
                    ->dateTime('d M Y — H:i')
                    ->since()
                    ->tooltip(fn (?object $state): ?string => $state?->format('Y-m-d H:i'))
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordClasses(fn (Announcement $record): ?string => $record->isExpired() ? 'fc-row-suspended' : null)
            ->filters([
                SelectFilter::make('status')
                    ->label(__('common.status'))
                    ->options(AnnouncementStatus::class)
                    ->multiple(),

                SelectFilter::make('severity')
                    ->label(__('content::content.announcement.fields.severity'))
                    ->options(AnnouncementSeverity::class)
                    ->multiple(),

                TernaryFilter::make('is_pinned')
                    ->label(__('content::content.announcement.fields.pinned')),

                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()->authorizationTooltip(),

                    Action::make('publish')
                        ->label(__('content::content.announcement.actions.publish'))
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        // السجل بيتبعت للـ Policy تلقائياً
                        ->authorize('publish')
                        ->authorizationTooltip()
                        ->requiresConfirmation()
                        ->modalHeading(__('content::content.announcement.confirm.publish_heading'))
                        ->modalDescription(__('content::content.announcement.confirm.publish_body'))
                        ->schema([
                            Toggle::make('notify_users')
                                ->label(__('content::content.announcement.fields.notify_users'))
                                ->default(true),
                        ])
                        ->action(fn (Announcement $record, array $data) => app(PublishAnnouncementAction::class)
                            ->handle(PublishAnnouncementData::fromArray(['id' => $record->getKey(), ...$data])))
                        ->successNotificationTitle(__('content::content.announcement.notifications.published')),

                    Action::make('pin')
                        ->label(__('content::content.announcement.actions.pin'))
                        ->icon('heroicon-o-bookmark')
                        ->color('warning')
                        ->authorize('pin')
                        ->authorizationTooltip()
                        ->action(fn (Announcement $record) => app(TogglePinAnnouncementAction::class)->handle($record, auth()->user())),

                    DeleteAction::make()->authorizationTooltip(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
                    ->label(__('common.actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // فحص كل سجل بالـ Policy — المنشور مثلاً بيتستبعد
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                    ExportBulkAction::make()
                        ->exporter(AnnouncementExporter::class)
                        ->authorize('export'),
                ]),
            ])
            ->headerActions([CreateAction::make()])
            ->emptyStateHeading(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results')
                : __('content::content.announcement.empty.heading'))
            ->emptyStateDescription(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results_description')
                : __('content::content.announcement.empty.description'))
            ->emptyStateIcon('heroicon-o-megaphone')
            ->emptyStateActions([CreateAction::make()->label(__('content::content.announcement.empty.cta'))]);
    }
}
