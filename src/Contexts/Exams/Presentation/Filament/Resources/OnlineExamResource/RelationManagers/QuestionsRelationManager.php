<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\RelationManagers;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Src\Contexts\Exams\Application\Actions\ImportQuestionsAction;
use Src\Contexts\Exams\Application\Services\QuestionCsv;
use Src\Contexts\Exams\Domain\Enums\QuestionType;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Exams\Presentation\Filament\Components\QuestionImageUpload;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Domain\Exceptions\DomainException;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            QuestionImageUpload::make('image', 'image', __('exams::exams.question.fields.image'))
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
                    QuestionImageUpload::make('choice_image', 'choice_image', __('exams::exams.question.fields.choice_image'))
                        ->columnSpanFull(),
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
                    Toggle::make('allow_images')
                        ->label(__('exams::exams.question.fields.allow_images'))
                        ->helperText(__('exams::exams.question.help.allow_images', ['max' => (int) config('exams.answer_images.max', 3)])),
                ])
                ->visible(fn (Get $get): bool => self::type($get) === QuestionType::Essay)
                ->columnSpanFull(),
            Textarea::make('explanation_text')
                ->label(__('exams::exams.question.fields.explanation'))
                ->helperText(__('exams::exams.question.help.explanation'))
                ->rows(2)
                ->columnSpanFull(),
            QuestionImageUpload::make('explanation_image', 'explanation_image', __('exams::exams.question.fields.explanation_image'))
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
                $this->importAction(),
                Action::make('importTemplate')
                    ->label(__('exams::exams.import.actions.template'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (): bool => $this->canImport())
                    ->action(fn (): StreamedResponse => response()->streamDownload(
                        function (): void {
                            echo app(QuestionCsv::class)->template();
                        },
                        'questions-template.csv',
                        ['Content-Type' => 'text/csv; charset=UTF-8'],
                    )),
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

    private function importAction(): Action
    {
        return Action::make('importQuestions')
            ->label(__('exams::exams.import.actions.import'))
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->visible(fn (): bool => $this->canImport())
            ->schema([
                FileUpload::make('file')
                    ->label(__('exams::exams.import.fields.file'))
                    ->helperText(__('exams::exams.import.help.file'))
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                    ->maxSize((int) config('exams.import.max_kb', 2048))
                    ->storeFiles(false)
                    ->required(),
            ])
            ->action(function (array $data, Action $action): void {
                $user = auth()->user();
                throw_unless($user instanceof User, AuthorizationException::class);

                $file = $data['file'] ?? null;
                $contents = $file instanceof TemporaryUploadedFile ? (string) $file->get() : '';

                /** @var OnlineExam $exam */
                $exam = $this->getOwnerRecord();

                try {
                    $result = app(ImportQuestionsAction::class)->handle($exam, $contents, $user);
                } catch (DomainException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();
                    $action->halt();

                    return;
                }

                if ($result->failed()) {
                    Notification::make()->danger()->persistent()
                        ->title(__('exams::exams.import.notifications.failed'))
                        ->body(nl2br(e($result->errorLines())))
                        ->send();
                    $action->halt();

                    return;
                }

                Notification::make()->success()
                    ->title(trans_choice('exams::exams.import.notifications.imported', $result->imported, ['count' => fc_number($result->imported)]))
                    ->send();
            });
    }

    private function canImport(): bool
    {
        return auth()->user()?->can('update', $this->getOwnerRecord()) ?? false;
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
