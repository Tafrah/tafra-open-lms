<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Contexts\Academy\Database\Factories\BootcampSectionFactory;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * جزء من الكورس (أسبوع/فصل) — `bootcamp_sections` في طفرة.
 */
final class BootcampSection extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<BootcampSectionFactory> */
    use HasFactory;

    protected $table = 'bootcamp_sections';

    /** @var list<string> */
    protected $fillable = [
        'bootcamp_id',
        'name',
        'order',
        'publish_status',
        'is_free_preview',
        'prerequisite_exam_id',
    ];

    /** @return BelongsTo<Bootcamp, $this> */
    public function bootcamp(): BelongsTo
    {
        return $this->belongsTo(Bootcamp::class);
    }

    /** @return HasMany<BootcampSectionItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BootcampSectionItem::class)->orderBy('order');
    }

    protected static function newFactory(): BootcampSectionFactory
    {
        return BootcampSectionFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'publish_status' => PublishStatus::class,
            'is_free_preview' => 'boolean',
            'order' => 'integer',
        ];
    }
}
