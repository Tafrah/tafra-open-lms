<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class BootcampSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bootcamp.name')
                    ->label(__('academy::academy.section.fields.bootcamp'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('academy::academy.section.fields.name'))
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('publish_status')
                    ->label(__('academy::academy.section.fields.publish_status'))
                    ->badge(),
            ])
            ->defaultSort('bootcamp_id')
            ->recordActions([EditAction::make()])
            ->emptyStateHeading(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results')
                : __('academy::academy.section.empty.heading'))
            ->emptyStateDescription(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results_description')
                : __('academy::academy.section.empty.description'));
    }
}
