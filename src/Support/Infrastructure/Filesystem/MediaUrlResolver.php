<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Filesystem;

use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Support\Application\Contracts\DiskResolver;

/**
 * الملفات الخاصة **مالهاش رابط دائم** — روابط موقّتة بس (docs/04 بند ٦).
 */
final readonly class MediaUrlResolver
{
    public function __construct(
        private DiskResolver $disks,
    ) {}

    public function url(Media $media, string $conversion = ''): string
    {
        if (! $this->disks->isPrivate($media->collection_name)) {
            return $conversion !== '' ? $media->getUrl($conversion) : $media->getUrl();
        }

        $expiry = now()->addMinutes((int) config('media-library.temporary_url_minutes', 10));

        // getTemporaryUrl() على S3 · الديسكات المحلية بتاخد route موقّع
        if ($this->supportsTemporaryUrls($media->disk)) {
            return $media->getTemporaryUrl($expiry, $conversion);
        }

        return URL::temporarySignedRoute('media.private', $expiry, [
            'media' => $media->getKey(),
            'conversion' => $conversion !== '' ? $conversion : null,
        ]);
    }

    private function supportsTemporaryUrls(string $disk): bool
    {
        return (string) config("filesystems.disks.{$disk}.driver") === 's3';
    }
}
