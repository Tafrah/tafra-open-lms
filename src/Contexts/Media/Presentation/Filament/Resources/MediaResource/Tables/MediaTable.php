<?php

declare(strict_types=1);

namespace Src\Contexts\Media\Presentation\Filament\Resources\MediaResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Src\Contexts\Media\Domain\Models\MediaItem;
use Src\Support\Application\Contracts\DiskResolver;
use Src\Support\Infrastructure\Filesystem\MediaUrlResolver;

final class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('media::media.item.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->description(fn (MediaItem $record): string => $record->file_name)
                    ->wrap(),

                TextColumn::make('collection_name')
                    ->label(__('media::media.item.fields.collection'))
                    ->badge()
                    ->color(fn (MediaItem $record): string => app(DiskResolver::class)->isPrivate($record->collection_name)
                        ? 'warning'
                        : 'gray')
                    ->icon(fn (MediaItem $record): string => app(DiskResolver::class)->isPrivate($record->collection_name)
                        ? 'heroicon-o-lock-closed'
                        : 'heroicon-o-globe-alt')
                    ->sortable(),

                TextColumn::make('mime_type')
                    ->label(__('media::media.item.fields.mime'))
                    ->extraAttributes(['class' => 'fc-code'])
                    ->toggleable(),

                TextColumn::make('size')
                    ->label(__('media::media.item.fields.size'))
                    ->formatStateUsing(fn (int $state): string => fc_number($state / 1024, 1).' KB')
                    ->extraAttributes(['class' => 'fc-number'])
                    ->sortable(),

                TextColumn::make('disk')
                    ->label(__('media::media.item.fields.disk'))
                    ->extraAttributes(['class' => 'fc-code'])
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime('d M Y — H:i')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('collection_name')
                    ->label(__('media::media.item.fields.collection'))
                    ->options(fn (): array => MediaItem::query()
                        ->distinct()
                        ->orderBy('collection_name')
                        ->pluck('collection_name', 'collection_name')
                        ->all())
                    ->multiple(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('download')
                        ->label(__('common.download'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->authorize('download')
                        ->authorizationTooltip()
                        // الملفات الخاصة بتاخد رابط مؤقت — مفيش رابط دائم
                        ->url(fn (MediaItem $record): string => app(MediaUrlResolver::class)->url($record), shouldOpenInNewTab: true),

                    DeleteAction::make()->authorizationTooltip(),
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
            ->emptyStateHeading(__('media::media.item.empty.heading'))
            ->emptyStateDescription(__('media::media.item.empty.description'))
            ->emptyStateIcon('heroicon-o-photo');
    }
}
