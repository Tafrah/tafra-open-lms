<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Src\Contexts\Exams\Database\Factories\QuestionFactory;
use Src\Contexts\Exams\Domain\Enums\QuestionType;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;
use Src\Support\Infrastructure\Persistence\Concerns\InteractsWithResolvedMedia;
use Src\Support\Infrastructure\Persistence\Concerns\SanitizesHtml;

/**
 * سؤال جوه امتحان. الاختيارات في `choices`، وإجابة المقالي النموذجية في `essay_question`.
 */
final class Question extends Model implements HasMedia
{
    use BelongsToTenant;

    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use InteractsWithResolvedMedia;
    use SanitizesHtml;

    protected $table = 'questions';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'online_exam_id',
        'order',
        'type',
        'text',
        'explanation_text',
        'mark',
        'hardness',
    ];

    /** @var list<string> */
    protected array $sanitizedHtml = ['text', 'explanation_text'];

    /** @return BelongsTo<OnlineExam, $this> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(OnlineExam::class, 'online_exam_id');
    }

    /** @return HasMany<Choice, $this> */
    public function choices(): HasMany
    {
        return $this->hasMany(Choice::class)->orderBy('order');
    }

    /** @return HasOne<EssayQuestion, $this> */
    public function essay(): HasOne
    {
        return $this->hasOne(EssayQuestion::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        // صورة جوه التفسير — بتظهر مع التفسير بعد التسليم بس
        $this->addMediaCollection('explanation_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    protected static function newFactory(): QuestionFactory
    {
        return QuestionFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'mark' => 'decimal:2',
            'order' => 'integer',
            'hardness' => 'integer',
        ];
    }
}
