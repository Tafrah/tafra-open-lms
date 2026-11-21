<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource\Pages;

use Filament\Actions\Action;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Src\Contexts\Billing\Domain\Models\RechargeCardGroup;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource;

final class ViewRechargeCardGroup extends ViewRecord
{
    protected static string $resource = RechargeCardGroupResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    TextEntry::make('name')->label(__('billing::billing.card_group.fields.name')),
                    TextEntry::make('type')->label(__('billing::billing.card_group.fields.type'))->badge(),
                    TextEntry::make('value')
                        ->label(__('billing::billing.card_group.fields.value'))
                        ->state(fn (RechargeCardGroup $record): string => $record->valueLabel()),
                    TextEntry::make('cards_count')->label(__('billing::billing.card_group.fields.count')),
                    TextEntry::make('expiry_date')->label(__('billing::billing.card_group.fields.expiry_date'))->dateTime('d M Y')->placeholder('∞'),
                    IconEntry::make('is_active')->label(__('billing::billing.card_group.fields.is_active'))->boolean(),
                ]),
        ]);
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label(__('billing::billing.card_group.actions.print'))
                ->icon('heroicon-o-printer')
                ->authorize('print')
                ->url(fn (RechargeCardGroup $record): string => route('billing.recharge-cards.print', $record), shouldOpenInNewTab: true),
            Action::make('csv')
                ->label(__('billing::billing.card_group.actions.csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->authorize('print')
                ->url(fn (RechargeCardGroup $record): string => route('billing.recharge-cards.csv', $record)),
            Action::make('toggle')
                ->label(fn (RechargeCardGroup $record): string => $record->is_active
                    ? __('billing::billing.card_group.actions.disable')
                    : __('billing::billing.card_group.actions.enable'))
                ->icon('heroicon-o-power')
                ->color(fn (RechargeCardGroup $record): string => $record->is_active ? 'danger' : 'success')
                ->authorize('update')
                ->requiresConfirmation()
                ->action(fn (RechargeCardGroup $record) => $record->update(['is_active' => ! $record->is_active])),
        ];
    }
}
