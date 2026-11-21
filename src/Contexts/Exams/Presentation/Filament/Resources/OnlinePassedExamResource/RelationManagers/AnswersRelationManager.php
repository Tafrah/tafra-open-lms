<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Exams\Application\Actions\GradeEssayAnswerAction;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Enums\QuestionType;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Identity\Domain\Models\User;

/** إجابات المحاولة — والمقالي بيتصحح من هنا (درجة + ملاحظة للطالب). */
final class AnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'answers';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('exams::exams.answer.plural');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['question.choices', 'question.essay', 'attempt']))
            ->columns([
                TextColumn::make('question.text')
                    ->label(__('exams::exams.answer.fields.question'))
                    ->formatStateUsing(fn (StudentExamAnswer $record): string => str((string) $record->question?->text)->stripTags()->limit(80)->toString())
                    ->wrap(),
                TextColumn::make('answer')
                    ->label(__('exams::exams.answer.fields.answer'))
                    ->state(fn (StudentExamAnswer $record): string => self::answerText($record))
                    ->wrap(),
                IconColumn::make('is_correct')
                    ->label(__('exams::exams.answer.fields.is_correct'))
                    ->boolean(),
                TextColumn::make('student_awarded_mark')
                    ->label(__('exams::exams.answer.fields.mark'))
                    ->formatStateUsing(fn (StudentExamAnswer $record): string => fc_number((float) $record->student_awarded_mark).' / '.fc_number((float) $record->max_question_mark)),
                TextColumn::make('grading_status')
                    ->label(__('exams::exams.answer.fields.grading_status'))
                    ->badge(),
            ])
            ->recordActions([
                Action::make('grade')
                    ->label(__('exams::exams.answer.actions.grade'))
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->authorize('grade')
                    ->visible(fn (StudentExamAnswer $record): bool => $record->question?->type === QuestionType::Essay)
                    ->modalDescription(fn (StudentExamAnswer $record): string => self::answerText($record))
                    ->fillForm(fn (StudentExamAnswer $record): array => [
                        'mark' => $record->grading_status === GradingStatus::Pending ? null : (float) $record->student_awarded_mark,
                        'feedback' => $record->grader_feedback,
                    ])
                    ->schema(fn (StudentExamAnswer $record): array => [
                        TextInput::make('mark')
                            ->label(__('exams::exams.answer.fields.mark'))
                            ->helperText(__('exams::exams.answer.help.max', ['max' => fc_number((float) $record->max_question_mark)]))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue((float) $record->max_question_mark)
                            ->required(),
                        Textarea::make('feedback')
                            ->label(__('exams::exams.answer.fields.feedback'))
                            ->helperText($record->question?->essay?->ideal_answer !== null
                                ? __('exams::exams.answer.help.ideal', ['answer' => (string) $record->question->essay->ideal_answer])
                                : null)
                            ->rows(3),
                    ])
                    ->action(function (StudentExamAnswer $record, array $data): void {
                        $user = auth()->user();
                        throw_unless($user instanceof User, AuthorizationException::class);

                        app(GradeEssayAnswerAction::class)->handle($record, (float) $data['mark'], $data['feedback'] ?? null, $user);
                    })
                    ->successNotificationTitle(__('exams::exams.answer.notifications.graded')),
            ])
            ->emptyStateHeading(__('exams::exams.answer.empty.heading'))
            ->emptyStateDescription(__('exams::exams.answer.empty.description'));
    }

    private static function answerText(StudentExamAnswer $record): string
    {
        $answer = (array) $record->answer;

        if (array_key_exists('text', $answer)) {
            return filled($answer['text']) ? (string) $answer['text'] : __('exams::exams.answer.blank');
        }

        $choice = $record->question?->choices->firstWhere('id', $answer['choice_id'] ?? null);

        return $choice !== null ? (string) $choice->text : __('exams::exams.answer.blank');
    }
}
