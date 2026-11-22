<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;
use Src\Support\Infrastructure\Persistence\Concerns\InteractsWithResolvedMedia;

/**
 * اختيار في سؤال اختيار من متعدد — نص، وصورة اختيارية.
 */
final class Choice extends Model implements HasMedia
{
    use BelongsToTenant;
    use InteractsWithMedia;
    use InteractsWithResolvedMedia;

    protected $table = 'choices';

    /** @var list<string> */
    protected $fillable = [
        'question_id',
        'text',
        'order',
        'is_correct',
    ];

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('choice_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'order' => 'integer',
        ];
    }
}
