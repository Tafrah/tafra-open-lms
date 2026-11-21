<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Src\Contexts\Academy\Domain\Models\EducationalLevel;
use Src\Contexts\Exams\Database\Factories\OnlineExamFactory;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * امتحان (اختيار من متعدد + مقالي) — `online_exams` في طفرة.
 */
final class OnlineExam extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<OnlineExamFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $table = 'online_exams';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'educational_level_id',
        'name',
        'description',
        'allowed_chances',
        'duration_in_seconds',
        'questions_number',
        'min_percentage',
        'published',
        'show_correct_answers_after_finish',
        'show_explanation',
        'random_questions',
        'random_choices',
        'start_at',
        'end_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<EducationalLevel, $this> */
    public function level(): BelongsTo
    {
        return $this->belongsTo(EducationalLevel::class, 'educational_level_id');
    }

    /** @return HasMany<Question, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    /** @return HasMany<OnlinePassedExam, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(OnlinePassedExam::class);
    }

    public function totalMark(): float
    {
        return (float) $this->questions()->sum('mark');
    }

    public function isOpenNow(): bool
    {
        $now = now();

        return $this->published
            && ($this->start_at === null || $this->start_at->lte($now))
            && ($this->end_at === null || $this->end_at->gte($now));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'published', 'allowed_chances', 'duration_in_seconds'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('exams');
    }

    protected static function newFactory(): OnlineExamFactory
    {
        return OnlineExamFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'show_correct_answers_after_finish' => 'boolean',
            'show_explanation' => 'boolean',
            'random_questions' => 'boolean',
            'random_choices' => 'boolean',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'min_percentage' => 'decimal:2',
            'allowed_chances' => 'integer',
            'duration_in_seconds' => 'integer',
            'questions_number' => 'integer',
        ];
    }
}
