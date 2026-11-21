<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Contexts\Exams\Database\Factories\OnlinePassedExamFactory;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * محاولة امتحان لطالب — `online_passed_exams` في طفرة.
 */
final class OnlinePassedExam extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<OnlinePassedExamFactory> */
    use HasFactory;

    protected $table = 'online_passed_exams';

    /** @var list<string> */
    protected $fillable = [
        'student_id',
        'online_exam_id',
        'bootcamp_section_item_id',
        'current_student_chance',
        'student_total_mark',
        'max_total_mark',
        'student_percentage',
        'finished',
        'grading_status',
        'question_order',
        'start_at',
        'end_at',
        'deadline_at',
        'graded_at',
    ];

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<OnlineExam, $this> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(OnlineExam::class, 'online_exam_id');
    }

    /** @return HasMany<StudentExamAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(StudentExamAnswer::class);
    }

    public function isExpired(): bool
    {
        return $this->deadline_at !== null && $this->deadline_at->isPast();
    }

    public function passed(): bool
    {
        $exam = $this->exam;

        return $this->finished
            && $this->grading_status !== GradingStatus::Pending
            && $exam !== null
            && (float) $this->student_percentage >= (float) $exam->min_percentage;
    }

    protected static function newFactory(): OnlinePassedExamFactory
    {
        return OnlinePassedExamFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'finished' => 'boolean',
            'grading_status' => GradingStatus::class,
            'question_order' => 'array',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'deadline_at' => 'datetime',
            'graded_at' => 'datetime',
            'student_total_mark' => 'decimal:2',
            'max_total_mark' => 'decimal:2',
            'student_percentage' => 'decimal:2',
            'current_student_chance' => 'integer',
        ];
    }
}
