<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Infrastructure\Updates;

use Illuminate\Support\Facades\Http;
use Src\Contexts\Installer\Infrastructure\InstallationState;
use Throwable;

/**
 * «فيه إصدار جديد؟» — آخر إصدار منشور على GitHub مقارنةً بالمتثبّت.
 *
 * الفحص نفسه من الجدولة (`lms:check-update`) مش من طلب المستخدم: اللوحة
 * بتقرا الكاش بس، فالـ GitHub البطيء أو المقفول مابيبطّأش أي صفحة.
 */
final readonly class ReleaseChecker
{
    /**
     * ملف مش كاش: الكاش معزول لكل مؤسسة (stancl)، والفحص بيشتغل من الجدولة
     * بره أي مؤسسة — فاللوحة جوه المؤسسة كانت هتقرا مفتاح تاني ومتلاقيش حاجة.
     */
    public static function path(): string
    {
        return storage_path('app/latest-release.json');
    }

    public function __construct(private InstallationState $state) {}

    /** @return array{version: string, url: string, checked_at: string}|null */
    public function check(): ?array
    {
        if (! (bool) config('installer.updates.enabled')) {
            return null;
        }

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'tafra-open-lms'])
                ->get((string) config('installer.updates.url'));
        } catch (Throwable) {
            return null;
        }

        $tag = $response->successful() ? $response->json('tag_name') : null;

        if (! is_string($tag) || preg_match('/^v?\d+\.\d+\.\d+/', $tag) !== 1) {
            return null;
        }

        $release = [
            'version' => ltrim($tag, 'v'),
            'url' => (string) ($response->json('html_url') ?? ''),
            'checked_at' => now()->toIso8601String(),
        ];

        @file_put_contents(self::path(), (string) json_encode($release), LOCK_EX);

        return $release;
    }

    /** @return array{version: string, url: string, checked_at: string}|null */
    public function newerRelease(): ?array
    {
        if (! (bool) config('installer.updates.enabled')) {
            return null;
        }

        $release = is_file(self::path()) ? json_decode((string) file_get_contents(self::path()), true) : null;

        if (! is_array($release) || ! is_string($release['version'] ?? null)) {
            return null;
        }

        return version_compare($release['version'], ltrim($this->state->version(), 'v'), '>') ? $release : null;
    }
}
