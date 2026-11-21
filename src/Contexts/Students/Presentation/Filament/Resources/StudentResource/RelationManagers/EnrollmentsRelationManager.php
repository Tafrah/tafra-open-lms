<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** تاريخ اشتراكات الطالب. */
final class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('students::students.student.relations.enrollments');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('bootcamp:id,name')->latest('id'))
            ->columns([
                TextColumn::make('bootcamp.name')->label(__('billing::billing.enrollment.fields.bootcamp'))->wrap(),
                TextColumn::make('type')->label(__('billing::billing.enrollment.fields.type'))->badge(),
                TextColumn::make('source')->label(__('billing::billing.enrollment.fields.source'))->badge(),
                TextColumn::make('expires_at')->label(__('billing::billing.enrollment.fields.expires_at'))->dateTime('d M Y')->placeholder('∞'),
                TextColumn::make('created_at')->label(__('common.created_at'))->dateTime('d M Y — H:i'),
            ])
            ->emptyStateHeading(__('students::students.student.relations.enrollments'))
            ->emptyStateDescription(__('students::students.student.empty_related'));
    }
}
