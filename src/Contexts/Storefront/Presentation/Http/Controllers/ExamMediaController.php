<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Contexts\Exams\Domain\Models\Choice;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Storefront\Presentation\Http\Controllers\Concerns\ResolvesStudent;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * صورة في امتحان (السؤال، الاختيار، التفسير، أو صورة إجابة الطالب).
 * الرابط موقّع **و** الصورة لازم تكون تبع المحاولة دي للطالب الداخل:
 * الصف بيتجاب من موديلات المؤسسة (أسئلة المحاولة / إجاباتها) مش من جدول الوسائط مباشرة.
 * صورة التفسير بتظهر بس لما النتيجة بتعرض التفسير.
 */
final class ExamMediaController
{
    use ResolvesStudent;

    public function __invoke(int $attempt, int $media): StreamedResponse
    {
        $record = OnlinePassedExam::query()
            ->with('exam')
            ->whereKey($attempt)
            ->where('student_id', $this->student()->getKey())
            ->first();

        abort_if($record === null || $record->exam === null, 404);

        $file = $this->find($record, $media);
        abort_if($file === null, 404);

        $disk = Storage::disk($file->disk);
        $path = $file->getPathRelativeToRoot();
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, $file->file_name, [
            'Content-Type' => $file->mime_type,
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex',
        ], 'inline');
    }

    private function find(OnlinePassedExam $attempt, int $mediaId): ?Media
    {
        $ids = array_map('intval', (array) $attempt->question_order);
        $questions = Question::query()->with(['media', 'choices.media'])->whereIn('id', $ids)->get();
        $explanations = $attempt->finished && $attempt->exam?->answersVisibleNow() && $attempt->exam->show_explanation;

        foreach ($questions as $question) {
            $match = $question->getMedia('image')->firstWhere('id', $mediaId)
                ?? ($explanations ? $question->getMedia('explanation_image')->firstWhere('id', $mediaId) : null);

            if ($match !== null) {
                return $match;
            }

            foreach ($question->choices as $choice) {
                /** @var Choice $choice */
                if (($match = $choice->getMedia('choice_image')->firstWhere('id', $mediaId)) !== null) {
                    return $match;
                }
            }
        }

        $answers = StudentExamAnswer::query()->with('media')->where('online_passed_exam_id', $attempt->getKey())->get();

        foreach ($answers as $answer) {
            if (($match = $answer->getMedia('answer_images')->firstWhere('id', $mediaId)) !== null) {
                return $match;
            }
        }

        return null;
    }
}
