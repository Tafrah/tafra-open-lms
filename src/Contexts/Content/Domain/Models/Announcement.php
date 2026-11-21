<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;
use Src\Contexts\Content\Database\Factories\AnnouncementFactory;
use Src\Contexts\Content\Domain\Enums\AnnouncementSeverity;
use Src\Contexts\Content\Domain\Enums\AnnouncementStatus;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;
use Src\Support\Infrastructure\Persistence\Concerns\InteractsWithResolvedMedia;
use Src\Support\Infrastructure\Persistence\Concerns\SanitizesHtml;

final class Announcement extends Model implements HasMedia
{
    use BelongsToTenant;          // ← الأهم: عزل المستأجر

    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    use HasTranslations;
    use InteractsWithMedia;
    use InteractsWithResolvedMedia;
    use LogsActivity;
    use SanitizesHtml;
    use SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['title', 'body'];

    /**
     * قائمة بيضاء صريحة. لاحظ إن `tenant_id` **مش** هنا —
     * الـ trait هو اللي بيحطه.
     *
     * @var list<string>
     */
    protected $fillable = [
        'author_id', 'title', 'body', 'status',
        'severity', 'is_pinned', 'published_at', 'expires_at',
    ];

    /**
     * محتوى المحرّر بيتنقّى عند الحفظ (docs/20 بند ٥).
     *
     * @var list<string>
     */
    protected array $sanitizedHtml = ['body'];

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @param Builder<Announcement> $query
     * @return Builder<Announcement>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', AnnouncementStatus::Published)
            ->where('published_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp']);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'severity', 'is_pinned', 'published_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('content');
    }

    protected static function newFactory(): AnnouncementFactory
    {
        return AnnouncementFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => AnnouncementStatus::class,
            'severity' => AnnouncementSeverity::class,
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
