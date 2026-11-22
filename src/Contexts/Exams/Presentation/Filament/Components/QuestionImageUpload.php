<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Components;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Src\Support\Application\Contracts\DiskResolver;

/**
 * رفع صورة في السؤال (السؤال نفسه، الاختيار، التفسير). الديسك من `DiskResolver`
 * والرؤية خاصة — الطالب بيشوفها برابط موقّع من المحاولة بتاعته بس.
 */
final class QuestionImageUpload
{
    public static function make(string $name, string $collection, string $label): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make($name)
            ->label($label)
            ->collection($collection)
            ->disk(fn (): string => app(DiskResolver::class)->for($collection))
            ->visibility('private')
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize((int) config('exams.question_image_max_kb', 4096))
            ->imageEditor(false);
    }
}
