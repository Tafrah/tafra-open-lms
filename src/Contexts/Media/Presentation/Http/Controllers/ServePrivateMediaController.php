<?php

declare(strict_types=1);

namespace Src\Contexts\Media\Presentation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Src\Contexts\Media\Domain\Models\MediaItem;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * الرابط موقّع **و** الـ Policy بتتفحص — التوقيع لوحده مش كفاية،
 * لأنه ممكن يتشارك (docs/20 بند ٤).
 */
final class ServePrivateMediaController
{
    public function __invoke(Request $request, MediaItem $media): StreamedResponse
    {
        Gate::authorize('download', $media);

        $conversion = $request->query('conversion');

        $path = filled($conversion)
            ? $media->getPathRelativeToRoot($conversion)
            : $media->getPathRelativeToRoot();

        abort_unless(Storage::disk($media->disk)->exists($path), 404, __('media::media.errors.not_found'));

        return Storage::disk($media->disk)->download($path, $media->file_name);
    }
}
