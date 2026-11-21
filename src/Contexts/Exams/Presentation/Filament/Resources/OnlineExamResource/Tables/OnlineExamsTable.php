<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Src\Contexts\Exams\Application\Actions\PublishExamAction;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Identity\Domain\Models\User;

final class OnlineExamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('exams::exams.exam.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->wrap(),
                TextColumn::make('questions_count')
                    ->label(__('exams::exams.exam.fields.questions_count'))
                    ->numeric(),
                TextColumn::make('duration_in_seconds')
                    ->label(__('exams::exams.exam.fields.duration_minutes'))
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? fc_number(intdiv($state, 60)) : '∞')
                    ->placeholder('∞'),
                TextColumn::make('allowed_chances')
                    ->label(__('exams::exams.exam.fields.allowed_chances'))
                    ->placeholder('∞'),
                IconColumn::make('published')
                    ->label(__('exams::exams.exam.fields.published'))
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('published')
                    ->label(__('exams::exams.exam.fields.published')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->authorizationTooltip(),
                    Action::make('publish')
                        ->label(__('exams::exams.exam.actions.publish'))
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->authorize('publish')
                        ->authorizationTooltip()
                        ->requiresConfirmation()
                        ->action(fn (OnlineExam $record) => app(PublishExamAction::class)->handle($record, self::actor()))
                        ->successNotificationTitle(__('exams::exams.exam.notifications.published')),
                    Action::make('unpublish')
                        ->label(__('exams::exams.exam.actions.unpublish'))
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->authorize('update')
                        ->visible(fn (OnlineExam $record): bool => $record->published)
                        ->requiresConfirmation()
                        ->action(fn (OnlineExam $record) => app(PublishExamAction::class)->unpublish($record, self::actor())),
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
                : __('exams::exams.exam.empty.heading'))
            ->emptyStateDescription(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results_description')
                : __('exams::exams.exam.empty.description'));
    }

    private static function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthorizationException::class);

        return $user;
    }
}
