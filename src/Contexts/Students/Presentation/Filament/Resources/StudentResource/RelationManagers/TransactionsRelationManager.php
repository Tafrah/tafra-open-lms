<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** حركات محفظة الطالب. */
final class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('students::students.student.relations.transactions');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->latest('id'))
            ->columns([
                TextColumn::make('type')->label(__('billing::billing.transaction.fields.type'))->badge(),
                TextColumn::make('amount')->label(__('billing::billing.transaction.fields.amount'))->numeric(2),
                TextColumn::make('source')->label(__('billing::billing.transaction.fields.source'))->badge()->color('gray'),
                TextColumn::make('balance_after')->label(__('billing::billing.transaction.fields.balance_after'))->numeric(2),
                TextColumn::make('notes')->label(__('billing::billing.transaction.fields.notes'))->limit(40)->placeholder('—'),
                TextColumn::make('created_at')->label(__('common.created_at'))->dateTime('d M Y — H:i'),
            ])
            ->emptyStateHeading(__('students::students.student.relations.transactions'))
            ->emptyStateDescription(__('students::students.student.empty_related'));
    }
}
