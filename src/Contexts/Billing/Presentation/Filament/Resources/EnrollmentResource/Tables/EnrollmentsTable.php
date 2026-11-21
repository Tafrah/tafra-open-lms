<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Billing\Domain\Models\Enrollment;

final class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label(__('billing::billing.enrollment.fields.student'))
                    ->description(fn (Enrollment $record): ?string => $record->student?->phone)
                    ->searchable(),
                TextColumn::make('bootcamp.name')
                    ->label(__('billing::billing.enrollment.fields.bootcamp'))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('type')
                    ->label(__('billing::billing.enrollment.fields.type'))
                    ->badge(),
                TextColumn::make('source')
                    ->label(__('billing::billing.enrollment.fields.source'))
                    ->badge(),
                TextColumn::make('expires_at')
                    ->label(__('billing::billing.enrollment.fields.expires_at'))
                    ->dateTime('d M Y')
                    ->placeholder('∞'),
                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime('d M Y — H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('bootcamp_id')
                    ->label(__('billing::billing.enrollment.fields.bootcamp'))
                    ->relationship('bootcamp', 'name')
                    ->searchable(),
                SelectFilter::make('source')
                    ->label(__('billing::billing.enrollment.fields.source'))
                    ->options(EnrollmentSource::class),
            ])
            ->emptyStateHeading(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results')
                : __('billing::billing.enrollment.empty.heading'))
            ->emptyStateDescription(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results_description')
                : __('billing::billing.enrollment.empty.description'));
    }
}
