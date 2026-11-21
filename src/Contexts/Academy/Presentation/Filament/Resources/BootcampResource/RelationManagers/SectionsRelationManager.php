<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Models\BootcampSection;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource;
use Src\Contexts\Exams\Domain\Models\OnlineExam;

/** أجزاء الكورس (أسبوع/فصل) — بالسحب للترتيب، وزرار «المحتوى» بيفتح عناصر الجزء. */
final class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('academy::academy.section.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('academy::academy.section.fields.name'))
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Select::make('publish_status')
                ->label(__('academy::academy.section.fields.publish_status'))
                ->options(PublishStatus::class)
                ->default(PublishStatus::Published)
                ->native(false)
                ->required(),
            Select::make('prerequisite_exam_id')
                ->label(__('academy::academy.section.fields.prerequisite_exam'))
                ->helperText(__('academy::academy.section.help.prerequisite_exam'))
                ->options(fn (): array => OnlineExam::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->nullable(),
            Toggle::make('is_free_preview')
                ->label(__('academy::academy.section.fields.is_free_preview'))
                ->helperText(__('academy::academy.section.help.is_free_preview')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('academy::academy.section.fields.name'))
                    ->weight('medium'),
                TextColumn::make('items_count')
                    ->label(__('academy::academy.section.fields.items_count'))
                    ->counts('items')
                    ->numeric(),
                TextColumn::make('publish_status')
                    ->label(__('academy::academy.section.fields.publish_status'))
                    ->badge(),
                IconColumn::make('is_free_preview')
                    ->label(__('academy::academy.section.fields.is_free_preview'))
                    ->boolean(),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'order' => (int) BootcampSection::query()
                            ->where('bootcamp_id', $this->getOwnerRecord()->getKey())
                            ->max('order') + 1,
                    ]),
            ])
            ->recordActions([
                Action::make('content')
                    ->label(__('academy::academy.section.actions.content'))
                    ->icon('heroicon-o-queue-list')
                    ->color('primary')
                    ->url(fn (BootcampSection $record): string => BootcampSectionResource::getUrl('edit', ['record' => $record])),
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->label(__('common.actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->emptyStateHeading(__('academy::academy.section.empty.heading'))
            ->emptyStateDescription(__('academy::academy.section.empty.description'));
    }
}
