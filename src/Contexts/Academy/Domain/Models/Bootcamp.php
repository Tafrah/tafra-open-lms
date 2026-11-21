<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Src\Contexts\Academy\Database\Factories\BootcampFactory;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;
use Src\Support\Infrastructure\Persistence\Concerns\InteractsWithResolvedMedia;
use Src\Support\Infrastructure\Persistence\Concerns\SanitizesHtml;

/**
 * الكورس / المحاضرة اللي بتتباع — `bootcamps` في طفرة.
 */
final class Bootcamp extends Model implements HasMedia
{
    use BelongsToTenant;

    /** @use HasFactory<BootcampFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use InteractsWithResolvedMedia;
    use LogsActivity;
    use SanitizesHtml;
    use SoftDeletes;

    protected $table = 'bootcamps';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'educational_level_id',
        'name',
        'slug',
        'short_description',
        'description',
        'promotional_video_url',
        'publish_status',
        'publish_date',
        'price',
        'price_currency',
        'discount_price',
        'discount_start_date',
        'discount_end_date',
        'can_watch_in_days',
        'is_featured',
        'featured_order',
    ];

    /** @var list<string> */
    protected array $sanitizedHtml = ['description'];

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

    /** @return HasMany<BootcampSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(BootcampSection::class)->orderBy('order');
    }

    /**
     * @param Builder<Bootcamp> $query
     * @return Builder<Bootcamp>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publish_status', PublishStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->publish_status === PublishStatus::Published;
    }

    public function hasActiveDiscount(): bool
    {
        if ($this->discount_price === null || (float) $this->discount_price >= (float) $this->price) {
            return false;
        }

        $now = now();

        return ($this->discount_start_date === null || $this->discount_start_date->lte($now))
            && ($this->discount_end_date === null || $this->discount_end_date->gte($now));
    }

    /** السعر اللي الطالب هيدفعه دلوقتي (بعد خصم المحاضرة لو شغال) */
    public function currentPrice(): float
    {
        return $this->hasActiveDiscount() ? (float) $this->discount_price : (float) $this->price;
    }

    public function isFree(): bool
    {
        return $this->currentPrice() <= 0.0;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'publish_status', 'price', 'discount_price'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('academy');
    }

    protected static function newFactory(): BootcampFactory
    {
        return BootcampFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'publish_status' => PublishStatus::class,
            'publish_date' => 'datetime',
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'discount_start_date' => 'datetime',
            'discount_end_date' => 'datetime',
            'can_watch_in_days' => 'integer',
            'is_featured' => 'boolean',
            'featured_order' => 'integer',
        ];
    }
}
