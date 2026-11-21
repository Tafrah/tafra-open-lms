<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Billing\Domain\Models\RechargeCard;

/** أكواد الدفعة: مين استخدم أنهي كود وإمتى. */
final class CardsRelationManager extends RelationManager
{
    protected static string $relationship = 'cards';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('billing::billing.card.plural');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->modifyQueryUsing(fn ($query) => $query->with('user:id,name,phone'))
            ->columns([
                TextColumn::make('code')
                    ->label(__('billing::billing.card.fields.code'))
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                TextColumn::make('user.name')
                    ->label(__('billing::billing.card.fields.used_by'))
                    ->description(fn (RechargeCard $record): ?string => $record->user?->phone)
                    ->placeholder('—'),
                TextColumn::make('used_at')
                    ->label(__('billing::billing.card.fields.used_at'))
                    ->dateTime('d M Y — H:i')
                    ->placeholder('—')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label(__('billing::billing.card.fields.is_active'))
                    ->disabled(fn (RechargeCard $record): bool => $record->isUsed()),
            ])
            ->filters([
                TernaryFilter::make('used')
                    ->label(__('billing::billing.card.filters.used'))
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('used_at'),
                        false: fn ($query) => $query->whereNull('used_at'),
                    ),
            ])
            ->recordActions([
                DeleteAction::make()->authorizationTooltip(),
            ])
            ->emptyStateHeading(__('billing::billing.card.empty.heading'))
            ->emptyStateDescription(__('billing::billing.card.empty.description'));
    }
}
