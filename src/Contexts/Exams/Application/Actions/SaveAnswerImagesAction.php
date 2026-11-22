<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Application\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Src\Contexts\Exams\Application\Services\OpenAttemptGuard;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Enums\QuestionType;
use Src\Contexts\Exams\Domain\Exceptions\ExamsException;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * صور إجابة المقالي (الطالب بيصوّر حلّه في الكشكول). نفس قفل `SaveAnswerAction`:
 * صورة بتوصل في نفس لحظة التسليم مابتتكتبش بعده. السؤال لازم يكون مسموح فيه
 * بالصور (`essay_question.allow_images`)، والعدد ليه حد (`exams.answer_images.max`).
 */
final readonly class SaveAnswerImagesAction
{
    private const COLLECTION = 'answer_images';

    /** صيغ الصور المقبولة — نفس `StudentExamAnswer::registerMediaCollections()` */
    public const MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(private OpenAttemptGuard $guard) {}

    /** @param list<UploadedFile> $images */
    public function handle(Student $student, OnlinePassedExam $attempt, int $questionId, array $images): StudentExamAnswer
    {
        $this->guard->assertWritable($student, $attempt, $questionId);
        $question = $this->imageQuestion($questionId);

        foreach ($images as $image) {
            throw_unless(
                in_array((string) $image->getMimeType(), self::MIMES, true)
                    && $image->getSize() <= (int) config('exams.answer_images.max_kb', 5120) * 1024,
                ExamsException::because('image_invalid', ['max' => (int) config('exams.answer_images.max_kb', 5120) / 1024]),
            );
        }

        return DB::transaction(function () use ($attempt, $question, $student, $images): StudentExamAnswer {
            $locked = OnlinePassedExam::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();
            throw_if($locked->finished, ExamsException::because('attempt_finished'));

            $answer = StudentExamAnswer::query()->firstOrCreate(
                ['online_passed_exam_id' => $attempt->getKey(), 'question_id' => $question->getKey()],
                [
                    'student_id' => $student->getKey(),
                    'answer' => ['text' => ''],
                    'max_question_mark' => $question->mark,
                    'grading_status' => GradingStatus::Pending,
                ],
            );

            $max = (int) config('exams.answer_images.max', 3);
            throw_if(
                $answer->getMedia(self::COLLECTION)->count() + count($images) > $max,
                ExamsException::because('too_many_images', ['max' => $max]),
            );

            foreach ($images as $image) {
                $answer->attachMedia($image, self::COLLECTION);
            }

            return $answer->refresh();
        });
    }

    /** الطالب بيشيل صورة رفعها (قبل التسليم بس) */
    public function remove(Student $student, OnlinePassedExam $attempt, int $questionId, int $mediaId): void
    {
        $this->guard->assertWritable($student, $attempt, $questionId);

        DB::transaction(function () use ($attempt, $questionId, $mediaId): void {
            $locked = OnlinePassedExam::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();
            throw_if($locked->finished, ExamsException::because('attempt_finished'));

            $answer = StudentExamAnswer::query()
                ->where('online_passed_exam_id', $attempt->getKey())
                ->where('question_id', $questionId)
                ->first();

            $media = $answer?->getMedia(self::COLLECTION)->firstWhere('id', $mediaId);
            throw_if($media === null, ExamsException::because('image_not_found'));

            $media->delete();
        });
    }

    private function imageQuestion(int $questionId): Question
    {
        /** @var Question $question */
        $question = Question::query()->with('essay')->findOrFail($questionId);

        throw_unless(
            $question->type === QuestionType::Essay && $question->essay?->allow_images === true,
            ExamsException::because('images_not_allowed'),
        );

        return $question;
    }
}
