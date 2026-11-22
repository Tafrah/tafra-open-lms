<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Support;

use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * روابط صور الامتحان للطالب: موقّعة ومؤقتة **ومربوطة بالمحاولة** — الكنترولر
 * بيتأكد إن الصورة تبع سؤال في المحاولة دي (أو إجابته هو) قبل ما يقدّمها.
 * مفيش رابط دائم لصورة سؤال، حتى لو الديسك عام.
 */
final class ExamMediaLinks
{
    public static function first(int $attemptId, ?HasMedia $model, string $collection): ?string
    {
        $media = $model?->getFirstMedia($collection);

        return $media !== null ? self::url($attemptId, $media) : null;
    }

    public static function url(int $attemptId, Media $media): string
    {
        return URL::temporarySignedRoute(
            'site.attempts.media',
            now()->addMinutes((int) config('exams.media_link_minutes', 120)),
            ['attempt' => $attemptId, 'media' => $media->getKey()],
        );
    }

    /** @return list<array{id: int, url: string}> */
    public static function all(int $attemptId, ?HasMedia $model, string $collection): array
    {
        if ($model === null) {
            return [];
        }

        return $model->getMedia($collection)
            ->map(fn (Media $media): array => ['id' => (int) $media->getKey(), 'url' => self::url($attemptId, $media)])
            ->values()
            ->all();
    }
}
