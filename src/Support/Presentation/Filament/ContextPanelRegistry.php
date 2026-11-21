<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Filament;

use Filament\Panel;

/**
 * كل سياق بيسجّل نفسه هنا، واللوحة بتقرا من هنا.
 *
 * ليه مش `Panel::configureUsing()` زي ما في docs/01؟ لأن الـ callback
 * بتشتغل جوه `Panel::make()` — **قبل** ما `->id()` يتحدد، فالشرط
 * `$panel->getId() !== 'admin'` مابيشتغلش. السجل ده بيحل المشكلة
 * وبيخلّي الترتيب صريح وقابل للاختبار.
 *
 * نفس السياق بيخدم اللوحتين: شاشات المؤسسة في `Presentation/Filament/*`،
 * وشاشات فريق التشغيل في `Presentation/Filament/Central/*` (docs/24 بند ١).
 */
final class ContextPanelRegistry
{
    /** اللوحات اللي بتقرا من مجلد فرعي جوه `Presentation/Filament` */
    private const PANEL_DIRECTORIES = [
        'central' => 'Central',
    ];

    /** @var array<string, list<array{namespace: string, path: string}>> */
    private array $contexts = [];

    public function registerContext(string $panelId, string $namespace, string $path): void
    {
        $this->contexts[$panelId][] = ['namespace' => $namespace, 'path' => $path];
    }

    public function applyTo(Panel $panel): Panel
    {
        $subdirectory = self::PANEL_DIRECTORIES[$panel->getId()] ?? null;

        foreach ($this->contexts[$panel->getId()] ?? [] as $context) {
            $base = rtrim($context['path'], '/').'/Presentation/Filament'.($subdirectory !== null ? "/{$subdirectory}" : '');
            $namespace = rtrim($context['namespace'], '\\').'\\Presentation\\Filament'.($subdirectory !== null ? "\\{$subdirectory}" : '');

            if (is_dir("{$base}/Resources")) {
                $panel->discoverResources(in: "{$base}/Resources", for: "{$namespace}\\Resources");
            }

            if (is_dir("{$base}/Pages")) {
                $panel->discoverPages(in: "{$base}/Pages", for: "{$namespace}\\Pages");
            }

            if (is_dir("{$base}/Widgets")) {
                $panel->discoverWidgets(in: "{$base}/Widgets", for: "{$namespace}\\Widgets");
            }

            if (is_dir("{$base}/Clusters")) {
                $panel->discoverClusters(in: "{$base}/Clusters", for: "{$namespace}\\Clusters");
            }
        }

        return $panel;
    }

    /** @return list<string> */
    public function registeredNamespaces(string $panelId = 'admin'): array
    {
        return array_map(
            static fn (array $context): string => $context['namespace'],
            $this->contexts[$panelId] ?? [],
        );
    }
}
