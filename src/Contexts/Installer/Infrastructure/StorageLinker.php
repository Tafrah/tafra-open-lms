<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Infrastructure;

use Src\Contexts\Installer\Domain\Enums\MediaServing;

/**
 * `storage:link` بنسخة بتتحمّل الاستضافة المشتركة.
 *
 * كتير من الاستضافات قافلة `symlink()` في `disable_functions`. وقتها
 * مانفشلش التثبيت: بنرجّع `Route`، والمثبّت بيكتب `PUBLIC_STORAGE_URL`
 * على `/uploads` اللي `ServePublicFileController` بيقدّمه.
 */
final readonly class StorageLinker
{
    public function __construct(
        private string $link,
        private string $target,
    ) {}

    public function link(): MediaServing
    {
        if (is_link($this->link)) {
            return MediaServing::Symlink;
        }

        if (! self::symlinksAvailable() || file_exists($this->link)) {
            return MediaServing::Route;
        }

        if (! is_dir($this->target)) {
            @mkdir($this->target, 0775, true);
        }

        try {
            $linked = @symlink($this->target, $this->link);
        } catch (\Throwable) {
            $linked = false;
        }

        return $linked ? MediaServing::Symlink : MediaServing::Route;
    }

    public static function symlinksAvailable(): bool
    {
        if (! function_exists('symlink')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array('symlink', $disabled, true);
    }
}
