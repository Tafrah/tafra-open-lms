<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp\SystemMap;

use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Src\Support\Presentation\Mcp\McpSession;

/**
 * «أنا مين وفين وأقدر أعمل إيه» — أول حاجة النموذج المفروض يسألها.
 *
 * نفس المحتوى بيرجع من أداة `describe_system` ومن مورد `fc://system/map`:
 * أدوات للعملاء اللي بتشتغل بالأدوات بس (ChatGPT)، ومورد للي بتقرا موارد.
 */
final readonly class SystemOverview
{
    private const SUMMARY_LENGTH = 280;

    public function __construct(
        private McpSession $session,
        private SystemMap $map,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $user = $this->session->user();
        $tenant = Filament::getTenant();

        return [
            'application' => (string) config('app.name'),
            'acting_as' => $user === null ? null : [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->preferredLocale(),
            ],
            'organization' => $tenant === null ? null : [
                'id' => $tenant->getKey(),
                'name' => $tenant->getAttribute('name'),
                'slug' => $tenant->getAttribute('slug'),
            ],
            'panel' => $this->session->panelId(),
            'access' => $this->session->canWrite() ? 'read_write' : 'read_only',
            'client' => $this->session->clientName(),
            'locales' => array_values((array) config('app.supported_locales')),
            'fallback_locale' => (string) config('app.fallback_locale'),
            'screens' => array_map(static fn (array $screen): array => [
                'key' => $screen['key'],
                'type' => $screen['type'],
                'title' => $screen['title'],
                'group' => $screen['group'],
                'section' => $screen['section'] ?? null,
                'url' => $screen['url'],
                'summary' => Str::limit((string) $screen['description'], self::SUMMARY_LENGTH),
                'tools' => $screen['tools'],
            ], $this->map->screens()),
            'guidance' => __('mcp.system.guidance'),
        ];
    }
}
