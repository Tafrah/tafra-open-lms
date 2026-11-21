<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Src\Contexts\Identity\Application\Actions\SwitchLocaleAction;
use Src\Contexts\Identity\Domain\Models\User;

/** مبدّل اللغة في التوب‑بار. */
final class LocaleSwitcher extends Component
{
    public function switch(string $locale): void
    {
        $user = auth()->user();

        app(SwitchLocaleAction::class)->handle($locale, $user instanceof User ? $user : null);

        $this->redirect(request()->header('Referer') ?? '/', navigate: false);
    }

    public function render(): View
    {
        return view('livewire.fc.locale-switcher', [
            'current' => app()->getLocale(),
            'locales' => (array) config('app.supported_locales', ['ar', 'en']),
        ]);
    }
}
