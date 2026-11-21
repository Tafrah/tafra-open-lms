<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * بيانات السؤال المقالي (الإجابة النموذجية للمصحح).
 */
final class EssayQuestion extends Model
{
    use BelongsToTenant;

    protected $table = 'essay_question';

    /** @var list<string> */
    protected $fillable = [
        'question_id',
        'ideal_answer',
        'allow_images',
    ];

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'allow_images' => 'boolean',
        ];
    }
}
