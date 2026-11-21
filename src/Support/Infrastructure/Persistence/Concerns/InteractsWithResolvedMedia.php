<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Persistence\Concerns;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Support\Application\Contracts\DiskResolver;
use Src\Support\Application\Contracts\TenantContext;

/**
 * بيضمن إن كل رفع بيعدّي على DiskResolver وبياخد tenant_id
 * — عشان محدش ينسى فيتكتب مسار مش معزول (docs/04 بند ٣ و٤).
 */
trait InteractsWithResolvedMedia
{
    public function attachMedia(string|UploadedFile $file, string $collection): Media
    {
        $resolver = app(DiskResolver::class);

        return $this->addMedia($file)
            ->withCustomProperties(['tenant_id' => app(TenantContext::class)->id()])
            ->storingConversionsOnDisk($resolver->forConversions($collection))
            ->toMediaCollection($collection, $resolver->for($collection));
    }
}
