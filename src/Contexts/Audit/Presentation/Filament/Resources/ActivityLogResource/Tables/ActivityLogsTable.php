<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Presentation\Filament\Resources\ActivityLogResource\Tables;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Audit\Domain\Models\ActivityLog;

final class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime('d M Y — H:i:s')
                    ->since()
                    ->tooltip(fn (?object $state): ?string => $state?->format('Y-m-d H:i:s'))
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('log_name')
                    ->label(__('audit::audit.log.fields.channel'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'security' => 'danger',
                        'identity' => 'info',
                        'tenancy' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => __('audit::audit.log.channels.'.($state ?? 'default')))
                    ->sortable(),

                TextColumn::make('description')
                    ->label(__('audit::audit.log.fields.description'))
                    ->formatStateUsing(fn (string $state): string => __('audit::audit.log.events.'.$state))
                    ->wrap(),

                TextColumn::make('subject_type')
                    ->label(__('audit::audit.log.fields.subject'))
                    ->formatStateUsing(fn (?string $state, ActivityLog $record): string => $state === null
                        ? '—'
                        : class_basename($state).' #'.$record->subject_id)
                    ->extraAttributes(['class' => 'fc-code'])
                    ->toggleable(),

                TextColumn::make('causer.name')
                    ->label(__('audit::audit.log.fields.causer'))
                    ->placeholder(__('audit::audit.log.system'))
                    ->toggleable(),

                TextColumn::make('properties.ip')
                    ->label(__('audit::audit.log.fields.ip'))
                    ->extraAttributes(['class' => 'fc-code'])
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('log_name')
                    ->label(__('audit::audit.log.fields.channel'))
                    ->options(fn (): array => ActivityLog::query()
                        ->distinct()
                        ->orderBy('log_name')
                        ->pluck('log_name', 'log_name')
                        ->filter()
                        ->map(fn (string $name): string => __("audit::audit.log.channels.{$name}"))
                        ->all())
                    ->multiple(),

                Filter::make('created_at')
                    ->label(__('common.created_between'))
                    ->schema([
                        DatePicker::make('from')->label(__('common.from')),
                        DatePicker::make('until')->label(__('common.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $d): Builder => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn (Builder $q, string $d): Builder => $q->whereDate('created_at', '<=', $d)))
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
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                Action::make('properties')
                    ->label(__('audit::audit.log.actions.details'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(__('audit::audit.log.actions.details'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('common.close'))
                    ->modalContent(fn (ActivityLog $record) => view('audit::filament.activity-properties', [
                        'properties' => $record->properties,
                    ])),
            ])
            ->emptyStateHeading(__('audit::audit.log.empty.heading'))
            ->emptyStateDescription(__('audit::audit.log.empty.description'))
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
