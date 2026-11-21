<?php

declare(strict_types=1);

namespace FutureCode\Onboarding\Tours;

use Illuminate\Support\Facades\Gate;

final class TourStep
{
    private string $title = '';

    private string $body = '';

    private string $element = '';

    private string $placement = 'bottom';

    private ?string $ability = null;

    private function __construct(public readonly string $key) {}

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function element(string $selector): self
    {
        $this->element = $selector;

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function placement(string $placement): self
    {
        $this->placement = $placement;

        return $this;
    }

    /** الخطوة بتتخطى تلقائياً لو المستخدم مالوش القدرة دي */
    public function ability(?string $ability): self
    {
        $this->ability = $ability;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->ability === null || Gate::allows($this->ability);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'element' => $this->element,
            'popover' => [
                'title' => $this->title,
                'description' => $this->body,
                'side' => $this->placement,
            ],
        ];
    }
}
