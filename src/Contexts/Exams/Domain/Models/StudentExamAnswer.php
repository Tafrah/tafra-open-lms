<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;
use Src\Support\Infrastructure\Persistence\Concerns\InteractsWithResolvedMedia;

/**
 * إجابة سؤال في محاولة. المقالي بيستنى تصحيح المدرس (grading_status = pending).
 */
final class StudentExamAnswer extends Model implements HasMedia
{
    use BelongsToTenant;
    use InteractsWithMedia;
    use InteractsWithResolvedMedia;

    protected $table = 'student_exam_answers';

    /** @var list<string> */
    protected $fillable = [
        'online_passed_exam_id',
        'student_id',
        'question_id',
        'answer',
        'is_correct',
        'student_awarded_mark',
        'max_question_mark',
        'grading_status',
        'graded_by',
        'graded_at',
        'grader_feedback',
    ];

    /** @return BelongsTo<OnlinePassedExam, $this> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OnlinePassedExam::class, 'online_passed_exam_id');
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /** @return BelongsTo<User, $this> */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('answer_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'answer' => 'array',
            'is_correct' => 'boolean',
            'grading_status' => GradingStatus::class,
            'graded_at' => 'datetime',
            'student_awarded_mark' => 'decimal:2',
            'max_question_mark' => 'decimal:2',
        ];
    }
}
