<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\RelationManagers;

use Closure;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Exams\Domain\Enums\QuestionType;
use Src\Contexts\Exams\Domain\Models\Question;

/** أسئلة الامتحان: اختيار من متعدد (بيتصحح لوحده) أو مقالي (المدرس بيصححه). */
final class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('exams::exams.question.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label(__('exams::exams.question.fields.type'))
                ->options(QuestionType::class)
                ->default(QuestionType::MultipleChoice)
                ->native(false)
                ->live()
                ->required(),
            TextInput::make('mark')
                ->label(__('exams::exams.question.fields.mark'))
                ->numeric()
                ->minValue(0.25)
                ->default(1)
                ->required(),
            RichEditor::make('text')
                ->label(__('exams::exams.question.fields.text'))
                ->required()
                ->columnSpanFull(),
            Repeater::make('choices')
                ->label(__('exams::exams.question.fields.choices'))
                ->relationship()
                ->orderColumn('order')
                ->schema([
                    TextInput::make('text')
                        ->label(__('exams::exams.question.fields.choice_text'))
                        ->required()
                        ->maxLength(1000),
                    Toggle::make('is_correct')
                        ->label(__('exams::exams.question.fields.is_correct'))
                        ->inline(false),
                ])
                ->columns(2)
                ->defaultItems(4)
                ->minItems(2)
                ->maxItems(8)
                ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    $correct = collect((array) $value)->filter(fn (mixed $choice): bool => (bool) (is_array($choice) ? ($choice['is_correct'] ?? false) : false))->count();

                    if ($correct !== 1) {
                        $fail(__('exams::exams.question.validation.one_correct'));
                    }
                })
                ->visible(fn (Get $get): bool => self::type($get) === QuestionType::MultipleChoice)
                ->columnSpanFull(),
            Group::make()
                ->relationship('essay', condition: fn (?array $state, Get $get): bool => self::type($get) === QuestionType::Essay)
                ->schema([
                    Textarea::make('ideal_answer')
                        ->label(__('exams::exams.question.fields.ideal_answer'))
                        ->helperText(__('exams::exams.question.help.ideal_answer'))
                        ->rows(3),
                ])
                ->visible(fn (Get $get): bool => self::type($get) === QuestionType::Essay)
                ->columnSpanFull(),
            Textarea::make('explanation_text')
                ->label(__('exams::exams.question.fields.explanation'))
                ->helperText(__('exams::exams.question.help.explanation'))
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('text')
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->numeric(),
                TextColumn::make('text')
                    ->label(__('exams::exams.question.fields.text'))
                    ->formatStateUsing(fn (Question $record): string => str((string) $record->text)->stripTags()->limit(90)->toString())
                    ->wrap(),
                TextColumn::make('type')
                    ->label(__('exams::exams.question.fields.type'))
                    ->badge(),
                TextColumn::make('mark')
                    ->label(__('exams::exams.question.fields.mark'))
                    ->numeric(),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'user_id' => auth()->id(),
                        'order' => (int) Question::query()
                            ->where('online_exam_id', $this->getOwnerRecord()->getKey())
                            ->max('order') + 1,
                    ])
                    ->after(fn () => $this->syncCount()),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()->after(fn () => $this->syncCount()),
                ])
                    ->label(__('common.actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->emptyStateHeading(__('exams::exams.question.empty.heading'))
            ->emptyStateDescription(__('exams::exams.question.empty.description'));
    }

    private function syncCount(): void
    {
        $exam = $this->getOwnerRecord();
        $exam->forceFill([
            'questions_number' => Question::query()->where('online_exam_id', $exam->getKey())->count(),
        ])->save();
    }

    private static function type(Get $get): ?QuestionType
    {
        $type = $get('type');

        return $type instanceof QuestionType ? $type : QuestionType::tryFrom((string) $type);
    }
}
