<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Src\Contexts\Academy\Application\Actions\PublishBootcampAction;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Identity\Domain\Models\User;

final class BootcampsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('thumbnail')
                    ->label(__('academy::academy.bootcamp.fields.thumbnail'))
                    ->collection('thumbnail')
                    ->imageHeight(40),
                TextColumn::make('name')
                    ->label(__('academy::academy.bootcamp.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->description(fn (Bootcamp $record): ?string => $record->level?->name)
                    ->wrap(),
                TextColumn::make('price')
                    ->label(__('academy::academy.bootcamp.fields.price'))
                    ->formatStateUsing(fn (Bootcamp $record): string => $record->isFree()
                        ? __('academy::academy.free')
                        : fc_number($record->currentPrice()).' '.__('academy::academy.currency'))
                    ->description(fn (Bootcamp $record): ?string => $record->hasActiveDiscount()
                        ? __('academy::academy.bootcamp.was', ['price' => fc_number((float) $record->price)])
                        : null)
                    ->sortable(),
                TextColumn::make('sections_count')
                    ->label(__('academy::academy.bootcamp.fields.sections_count'))
                    ->numeric(),
                TextColumn::make('publish_status')
                    ->label(__('common.status'))
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label(__('academy::academy.bootcamp.fields.is_featured'))
                    ->boolean()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('publish_status')
                    ->label(__('common.status'))
                    ->options(PublishStatus::class),
                SelectFilter::make('educational_level_id')
                    ->label(__('academy::academy.bootcamp.fields.level'))
                    ->relationship('level', 'name'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->authorizationTooltip(),
                    Action::make('publish')
                        ->label(__('academy::academy.bootcamp.actions.publish'))
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->authorize('publish')
                        ->authorizationTooltip()
                        ->requiresConfirmation()
                        ->action(fn (Bootcamp $record) => app(PublishBootcampAction::class)->handle($record, self::actor()))
                        ->successNotificationTitle(__('academy::academy.bootcamp.notifications.published')),
                    Action::make('unpublish')
                        ->label(__('academy::academy.bootcamp.actions.unpublish'))
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->authorize('update')
                        ->visible(fn (Bootcamp $record): bool => $record->isPublished())
                        ->requiresConfirmation()
                        ->action(fn (Bootcamp $record) => app(PublishBootcampAction::class)->unpublish($record, self::actor()))
                        ->successNotificationTitle(__('academy::academy.bootcamp.notifications.unpublished')),
                    DeleteAction::make()->authorizationTooltip(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
                    ->label(__('common.actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ])
            ->headerActions([CreateAction::make()])
            ->emptyStateHeading(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results')
                : __('academy::academy.bootcamp.empty.heading'))
            ->emptyStateDescription(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results_description')
                : __('academy::academy.bootcamp.empty.description'));
    }

    private static function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthorizationException::class);

        return $user;
    }
}
