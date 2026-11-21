<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp;

use Laravel\Mcp\Server\Tool;

/**
 * كل سياق بيسجّل إعلاناته وأدواته هنا، والخادم بيقرا من هنا.
 *
 * نفس سبب `ContextPanelRegistry` (docs/21 بند ٤): الخادم مايعرفش السياقات.
 * `ContextServiceProvider` بيكتشف `Presentation/Mcp/*` في `register()`، فميزة
 * جديدة بإعلانها بتظهر للذكاء الاصطناعي من غير ما حد يفتكر يضيفها لقايمة.
 */
final class McpRegistry
{
    /** @var array<string, array<class-string<McpResource>, true>> */
    private array $resources = [];

    /** @var array<string, array<class-string<Tool>, true>> */
    private array $tools = [];

    /** @param class-string<McpResource> $class */
    public function resource(string $panelId, string $class): void
    {
        $this->resources[$panelId][$class] = true;
    }

    /** @param class-string<Tool> $class أداة مستقلة مش مرتبطة بمورد */
    public function tool(string $panelId, string $class): void
    {
        $this->tools[$panelId][$class] = true;
    }

    /** @return list<class-string<McpResource>> */
    public function resourcesFor(string $panelId): array
    {
        return array_keys($this->resources[$panelId] ?? []);
    }

    /** @return list<class-string<Tool>> */
    public function toolsFor(string $panelId): array
    {
        return array_keys($this->tools[$panelId] ?? []);
    }

    /** @return list<string> */
    public function panels(): array
    {
        return array_values(array_unique([...array_keys($this->resources), ...array_keys($this->tools)]));
    }

    /** الإعلان اللي بيعكس مورد Filament ده — أو `null`. */
    public function declarationFor(string $filamentResource, ?string $panelId = null): ?McpResource
    {
        foreach ($panelId === null ? $this->panels() : [$panelId] as $panel) {
            foreach ($this->resourcesFor($panel) as $class) {
                if ($class::filamentResource() === $filamentResource) {
                    return app($class);
                }
            }
        }

        return null;
    }
}
