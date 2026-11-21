<?php

declare(strict_types=1);

namespace FutureCode\Onboarding\Livewire;

use FutureCode\Onboarding\Facades\Onboarding;
use FutureCode\Onboarding\Tours\Tour;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * مشغّل الجولات.
 *
 * بيحمّل خطوات الجولة (بعد فلترة الصلاحيات) ويسلّمها لـ driver.js
 * المحزوم مع Vite — **مش من CDN**، عشان التطبيق يشتغل في بيئة
 * مقفولة (docs/15 بند ٥).
 */
final class TourRunner extends Component
{
    public string $page = '';

    /** @var array<int, array<string, mixed>> */
    public array $steps = [];

    public ?string $tourKey = null;

    public bool $autoStart = false;

    public function mount(string $page): void
    {
        $this->page = $page;

        $tour = Onboarding::toursFor($page)->first();

        if (! $tour instanceof Tour) {
            return;
        }

        $this->tourKey = $tour::key();
        $this->steps = $tour->payload();
        $this->autoStart = $this->steps !== [] && Onboarding::shouldAutoStart($tour, auth()->user());
    }

    #[On('fc-tour-completed')]
    public function markCompleted(string $key): void
    {
        Onboarding::progress()->markTourCompleted($key);
    }

    public function replay(): void
    {
        $this->dispatch('fc-tour-start', steps: $this->steps, key: $this->tourKey);
    }

    public function render(): View
    {
        return view('fc-onboarding::tour-runner');
    }
}
