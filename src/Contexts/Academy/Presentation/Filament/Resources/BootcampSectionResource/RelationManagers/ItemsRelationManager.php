<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Enums\SectionItemType;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Academy\Domain\Models\OnlineLesson;
use Src\Contexts\Exams\Domain\Models\OnlineExam;

/** عناصر الجزء: كل عنصر درس واحد من المكتبة أو امتحان واحد. */
final class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('academy::academy.item.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label(__('academy::academy.item.fields.type'))
                ->options(SectionItemType::class)
                ->default(SectionItemType::Lesson)
                ->native(false)
                ->live()
                ->required(),
            Select::make('online_lesson_id')
                ->label(__('academy::academy.item.fields.lesson'))
                ->options(fn (): array => OnlineLesson::query()->latest('id')->limit(500)->pluck('name', 'id')->all())
                ->searchable()
                ->required(fn (Get $get): bool => self::type($get) === SectionItemType::Lesson)
                ->visible(fn (Get $get): bool => self::type($get) === SectionItemType::Lesson),
            Select::make('online_exam_id')
                ->label(__('academy::academy.item.fields.exam'))
                ->options(fn (): array => OnlineExam::query()->latest('id')->limit(500)->pluck('name', 'id')->all())
                ->searchable()
                ->required(fn (Get $get): bool => self::type($get) === SectionItemType::Exam)
                ->visible(fn (Get $get): bool => self::type($get) === SectionItemType::Exam),
            TextInput::make('name')
                ->label(__('academy::academy.item.fields.name'))
                ->helperText(__('academy::academy.item.help.name'))
                ->maxLength(255),
            Select::make('publish_status')
                ->label(__('academy::academy.item.fields.publish_status'))
                ->options(PublishStatus::class)
                ->default(PublishStatus::Published)
                ->native(false)
                ->required(),
            TextInput::make('allowed_views_number')
                ->label(__('academy::academy.item.fields.allowed_views_number'))
                ->helperText(__('academy::academy.item.help.allowed_views_number'))
                ->numeric()
                ->minValue(1)
                ->visible(fn (Get $get): bool => self::type($get) === SectionItemType::Lesson),
            Select::make('prerequisite_exam_id')
                ->label(__('academy::academy.item.fields.prerequisite_exam'))
                ->options(fn (): array => OnlineExam::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->nullable(),
            Toggle::make('is_free_preview')
                ->label(__('academy::academy.item.fields.is_free_preview')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn ($query) => $query->with(['lesson:id,name,type', 'exam:id,name']))
            ->columns([
                TextColumn::make('type')
                    ->label(__('academy::academy.item.fields.type'))
                    ->badge(),
                TextColumn::make('name')
                    ->label(__('academy::academy.item.fields.name'))
                    ->weight('medium')
                    ->description(fn (BootcampSectionItem $record): ?string => $record->lesson?->type?->getLabel()),
                TextColumn::make('allowed_views_number')
                    ->label(__('academy::academy.item.fields.allowed_views_number'))
                    ->placeholder('∞'),
                TextColumn::make('publish_status')
                    ->label(__('academy::academy.item.fields.publish_status'))
                    ->badge(),
                IconColumn::make('is_free_preview')
                    ->label(__('academy::academy.item.fields.is_free_preview'))
                    ->boolean(),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data): array => self::prepare([
                        ...$data,
                        'order' => (int) BootcampSectionItem::query()
                            ->where('bootcamp_section_id', $this->getOwnerRecord()->getKey())
                            ->max('order') + 1,
                    ])),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->mutateDataUsing(fn (array $data): array => self::prepare($data)),
                    DeleteAction::make(),
                ])
                    ->label(__('common.actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->emptyStateHeading(__('academy::academy.item.empty.heading'))
            ->emptyStateDescription(__('academy::academy.item.empty.description'));
    }

    private static function type(Get $get): ?SectionItemType
    {
        $type = $get('type');

        return $type instanceof SectionItemType ? $type : SectionItemType::tryFrom((string) $type);
    }

    /**
     * العنصر بيشاور على درس أو امتحان — مش الاتنين. والاسم الفاضي بياخد اسم الدرس/الامتحان.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function prepare(array $data): array
    {
        $type = $data['type'] instanceof SectionItemType ? $data['type'] : SectionItemType::from((string) $data['type']);

        if ($type === SectionItemType::Lesson) {
            $data['online_exam_id'] = null;
            $fallback = OnlineLesson::query()->whereKey($data['online_lesson_id'] ?? 0)->value('name');
        } else {
            $data['online_lesson_id'] = null;
            $data['allowed_views_number'] = null;
            $fallback = OnlineExam::query()->whereKey($data['online_exam_id'] ?? 0)->value('name');
        }

        if (blank($data['name'] ?? null)) {
            $data['name'] = (string) $fallback;
        }

        return $data;
    }
}
