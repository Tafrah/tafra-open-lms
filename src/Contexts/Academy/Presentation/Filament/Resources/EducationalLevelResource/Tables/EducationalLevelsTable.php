<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\EducationalLevelResource\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class EducationalLevelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label(__('academy::academy.level.fields.color')),
                TextColumn::make('name')
                    ->label(__('academy::academy.level.fields.name'))
                    ->searchable()
                    ->weight(FontWeight::Medium),
                TextColumn::make('code')
                    ->label(__('academy::academy.level.fields.code'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('students_count')
                    ->label(__('academy::academy.level.fields.students_count'))
                    ->counts('students')
                    ->numeric(),
                TextColumn::make('bootcamps_count')
                    ->label(__('academy::academy.level.fields.bootcamps_count'))
                    ->counts('bootcamps')
                    ->numeric(),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->authorizationTooltip(),
                    DeleteAction::make()->authorizationTooltip(),
                    RestoreAction::make(),
                ])
                    ->label(__('common.actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ])
            ->filters([TrashedFilter::make()])
            ->emptyStateHeading(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results')
                : __('academy::academy.level.empty.heading'))
            ->emptyStateDescription(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results_description')
                : __('academy::academy.level.empty.description'));
    }
}
