<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Src\Contexts\Academy\Database\Factories\OnlineLessonFactory;
use Src\Contexts\Academy\Domain\Enums\LessonType;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;
use Src\Support\Infrastructure\Persistence\Concerns\InteractsWithResolvedMedia;
use Src\Support\Infrastructure\Persistence\Concerns\SanitizesHtml;

/**
 * درس في مكتبة الدروس (فيديو يوتيوب، ملف PDF، أو نص) — بيتحط في أي كورس كعنصر.
 */
final class OnlineLesson extends Model implements HasMedia
{
    use BelongsToTenant;

    /** @use HasFactory<OnlineLessonFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use InteractsWithResolvedMedia;
    use LogsActivity;
    use SanitizesHtml;
    use SoftDeletes;

    protected $table = 'online_lessons';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'description',
        'text',
        'external_url',
        'duration_in_seconds',
        'number_of_pages',
        'allow_download',
    ];

    /** @var list<string> */
    protected array $sanitizedHtml = ['text'];

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<BootcampSectionItem, $this> */
    public function sectionItems(): HasMany
    {
        return $this->hasMany(BootcampSectionItem::class);
    }

    /** رقم فيديو يوتيوب من أي شكل رابط (watch?v= / youtu.be / embed / shorts) */
    public function youtubeId(): ?string
    {
        $url = (string) $this->external_url;

        if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~', $url, $m) === 1) {
            return $m[1];
        }

        return preg_match('~^[A-Za-z0-9_-]{11}$~', $url) === 1 ? $url : null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'external_url'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('academy');
    }

    protected static function newFactory(): OnlineLessonFactory
    {
        return OnlineLessonFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'duration_in_seconds' => 'integer',
            'number_of_pages' => 'integer',
            'allow_download' => 'boolean',
        ];
    }
}
