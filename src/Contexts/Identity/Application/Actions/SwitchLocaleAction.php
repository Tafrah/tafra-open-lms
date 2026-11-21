<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Application\Actions;

use Src\Contexts\Identity\Domain\Models\User;

final readonly class SwitchLocaleAction
{
    public function handle(string $locale, ?User $user = null): string
    {
        $supported = (array) config('app.supported_locales', ['ar', 'en']);

        abort_unless(in_array($locale, $supported, true), 400);

        // الجلسة مش كفاية — الطوابير والبريد بيقروا من قاعدة البيانات
        $user?->forceFill(['locale' => $locale])->saveQuietly();

        session()->put('locale', $locale);
        app()->setLocale($locale);

        return $locale;
    }
}
