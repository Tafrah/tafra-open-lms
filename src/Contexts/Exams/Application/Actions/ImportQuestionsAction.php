<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Application\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Exams\Application\DataObjects\QuestionImportResult;
use Src\Contexts\Exams\Application\Services\QuestionCsv;
use Src\Contexts\Exams\Domain\Enums\QuestionType;
use Src\Contexts\Exams\Domain\Exceptions\ExamsException;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Identity\Domain\Models\User;

/**
 * استيراد أسئلة اختيار من متعدد من ملف CSV لامتحان.
 * كل الصفوف بتتفحص الأول؛ صف واحد غلط = ولا سؤال بيتضاف، والأخطاء بترجع برقم الصف.
 */
final readonly class ImportQuestionsAction
{
    private const MAX_TEXT = 5000;

    public function __construct(private QuestionCsv $csv) {}

    public function handle(OnlineExam $exam, string $contents, User $actor): QuestionImportResult
    {
        Gate::forUser($actor)->authorize('update', $exam);

        ['headers' => $headers, 'rows' => $rows] = $this->csv->read($contents);

        $missing = array_diff(['question', 'choice_1', 'choice_2', 'correct'], $headers);
        throw_if($missing !== [], ExamsException::because('import_headers', ['columns' => implode(', ', $missing)]));
        throw_if($rows === [], ExamsException::because('import_empty'));

        $max = (int) config('exams.import.max_rows', 500);
        throw_if(count($rows) > $max, ExamsException::because('import_too_many', ['max' => $max]));

        $parsed = [];
        $errors = [];

        foreach ($rows as $line => $row) {
            [$question, $rowErrors] = $this->parse($row);

            if ($rowErrors !== []) {
                $errors[$line] = $rowErrors;

                continue;
            }

            $parsed[] = $question;
        }

        if ($errors !== []) {
            return new QuestionImportResult(0, $errors);
        }

        DB::transaction(function () use ($exam, $parsed, $actor): void {
            $order = (int) Question::query()->where('online_exam_id', $exam->getKey())->lockForUpdate()->max('order');

            foreach ($parsed as $data) {
                $question = Question::query()->create([
                    'user_id' => $actor->getKey(),
                    'online_exam_id' => $exam->getKey(),
                    'order' => ++$order,
                    'type' => QuestionType::MultipleChoice,
                    'text' => '<p>'.nl2br(e($data['text']), false).'</p>',
                    'explanation_text' => $data['explanation'],
                    'mark' => $data['mark'],
                ]);

                foreach ($data['choices'] as $index => $text) {
                    $question->choices()->create([
                        'text' => $text,
                        'order' => $index + 1,
                        'is_correct' => $index === $data['correct'],
                    ]);
                }
            }

            $exam->forceFill([
                'questions_number' => Question::query()->where('online_exam_id', $exam->getKey())->count(),
            ])->save();
        });

        return new QuestionImportResult(count($parsed), []);
    }

    /**
     * @param array<string, string> $row
     * @return array{0: array{text: string, choices: list<string>, correct: int, mark: float, explanation: string|null}, 1: list<string>}
     */
    private function parse(array $row): array
    {
        $errors = [];
        $text = (string) ($row['question'] ?? '');

        if ($text === '') {
            $errors[] = __('exams::exams.import.errors.question_required');
        } elseif (mb_strlen($text) > self::MAX_TEXT) {
            $errors[] = __('exams::exams.import.errors.question_too_long', ['max' => self::MAX_TEXT]);
        }

        // الاختيارات بترتيب أعمدتها؛ الفاضي في النص بيتشال والرقم الصح بيتحسب على الأعمدة
        $byColumn = [];

        for ($i = 1; $i <= $this->csv->maxChoices(); $i++) {
            $value = (string) ($row["choice_{$i}"] ?? '');

            if ($value !== '') {
                $byColumn[$i] = mb_substr($value, 0, 1000);
            }
        }

        if (count($byColumn) < 2) {
            $errors[] = __('exams::exams.import.errors.two_choices');
        }

        $correctRaw = (string) ($row['correct'] ?? '');
        $correct = filter_var($this->westernDigits($correctRaw), FILTER_VALIDATE_INT);

        if ($correct === false || ! array_key_exists($correct, $byColumn)) {
            $errors[] = __('exams::exams.import.errors.correct_invalid', ['max' => $this->csv->maxChoices()]);
        }

        $markRaw = $this->westernDigits((string) ($row['mark'] ?? ''));
        $mark = $markRaw === '' ? 1.0 : filter_var($markRaw, FILTER_VALIDATE_FLOAT);

        if ($mark === false || $mark < 0.25 || $mark > 1000) {
            $errors[] = __('exams::exams.import.errors.mark_invalid');
        }

        $explanation = (string) ($row['explanation'] ?? '');

        return [[
            'text' => $text,
            'choices' => array_values($byColumn),
            'correct' => $correct === false ? -1 : (int) array_search($correct, array_keys($byColumn), true),
            'mark' => $mark === false ? 1.0 : round((float) $mark, 2),
            'explanation' => $explanation !== '' ? mb_substr($explanation, 0, self::MAX_TEXT) : null,
        ], $errors];
    }

    /** المدرس ممكن يكتب الأرقام عربي (١، ٢) */
    private function westernDigits(string $value): string
    {
        return strtr(trim($value), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٫' => '.']);
    }
}
