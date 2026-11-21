<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Contexts\Academy\Database\Factories\BootcampSectionItemFactory;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Enums\SectionItemType;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * عنصر جوه الجزء: درس واحد أو امتحان واحد — `bootcamp_section_items` في طفرة.
 */
final class BootcampSectionItem extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<BootcampSectionItemFactory> */
    use HasFactory;

    protected $table = 'bootcamp_section_items';

    /** @var list<string> */
    protected $fillable = [
        'bootcamp_section_id',
        'name',
        'order',
        'type',
        'online_lesson_id',
        'online_exam_id',
        'prerequisite_exam_id',
        'allowed_views_number',
        'publish_status',
        'is_free_preview',
    ];

    /** @return BelongsTo<BootcampSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(BootcampSection::class, 'bootcamp_section_id');
    }

    /** @return BelongsTo<OnlineLesson, $this> */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(OnlineLesson::class, 'online_lesson_id');
    }

    /** @return BelongsTo<OnlineExam, $this> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(OnlineExam::class, 'online_exam_id');
    }

    /** @return HasMany<SectionItemView, $this> */
    public function views(): HasMany
    {
        return $this->hasMany(SectionItemView::class);
    }

    protected static function newFactory(): BootcampSectionItemFactory
    {
        return BootcampSectionItemFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => SectionItemType::class,
            'publish_status' => PublishStatus::class,
            'is_free_preview' => 'boolean',
            'order' => 'integer',
            'allowed_views_number' => 'integer',
        ];
    }
}
