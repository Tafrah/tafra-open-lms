<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp\SystemMap;

use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Resources\Resource as FilamentResource;
use FutureCode\Onboarding\OnboardingManager;
use FutureCode\Onboarding\Tours\Tour;
use FutureCode\Onboarding\Tours\TourStep;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Src\Support\Presentation\Mcp\Contracts\DescribesForAi;
use Src\Support\Presentation\Mcp\McpRegistry;
use Src\Support\Presentation\Mcp\McpSession;
use Src\Support\Presentation\Mcp\McpToolFactory;
use Src\Support\Presentation\Mcp\Tools\ResourceTool;
use Throwable;

/**
 * خريطة النظام زي ما المستخدم الحالي شايفه — بتتبني من اللوحة نفسها كل طلب.
 *
 * مفيش قايمة صفحات مكتوبة بالإيد: أي مورد أو صفحة أو تبويب بيتضاف للوحة
 * بيظهر هنا لوحده باسمه ومجموعته ومساره ووصفه والأدوات اللي بتشتغل عليه.
 * الوصف من `DescribesForAi` لو موجود، وإلا من خطوات جولة الشاشة (اللي
 * إلزامية — قاعدة ٩)، وإلا من سبب الإعفاء. `McpCoverageTest` بيتأكد إن
 * مفيش شاشة من غير وصف بكل اللغات.
 *
 * الشاشات اللي المستخدم مايقدرش يفتحها مابتظهرش — زي السايدبار بالظبط.
 */
final class SystemMap
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $cache = [];

    public function __construct(
        private readonly McpSession $session,
        private readonly McpRegistry $registry,
    ) {}

    /**
     * كل الشاشات المتاحة للمستخدم في لوحة الجلسة.
     *
     * @return list<array<string, mixed>>
     */
    public function screens(?string $panelId = null, bool $onlyAccessible = true): array
    {
        $panelId ??= $this->session->panelId();
        $cacheKey = $panelId.'|'.(int) $onlyAccessible.'|'.app()->getLocale();

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $panel = Filament::getPanel($panelId);
        $screens = [];

        foreach ($panel->getResources() as $resource) {
            $screen = $this->resourceScreen($resource, $panel);

            if ($screen !== null && (! $onlyAccessible || $screen['accessible'])) {
                $screens[] = $screen;
            }
        }

        foreach ($this->pageClasses($panel) as $page) {
            $screen = $this->pageScreen($page, $panel);

            if ($screen !== null && (! $onlyAccessible || $screen['accessible'])) {
                $screens[] = $screen;
            }
        }

        usort($screens, static fn (array $a, array $b): int => [$a['group'] ?? '', $a['title']] <=> [$b['group'] ?? '', $b['title']]);

        return $this->cache[$cacheKey] = $screens;
    }

    /** @return array<string, mixed>|null شاشة واحدة بمفتاحها — بالتفاصيل كلها */
    public function screen(string $key, ?string $panelId = null): ?array
    {
        foreach ($this->screens($panelId) as $screen) {
            if ($screen['key'] === $key) {
                return $screen;
            }
        }

        return null;
    }

    /** وصف مورد Filament — من شاشة القائمة بتاعته. */
    public function describeResource(string $resource, ?string $panelId = null): string
    {
        $panelId ??= $this->session->panelId();
        $index = $this->indexPage($resource);

        return $this->describe($index ?? $resource, $panelId);
    }

    /**
     * وصف شاشة: `DescribesForAi` ← خطوات الجولة ← سبب الإعفاء.
     *
     * @param class-string $class
     */
    public function describe(string $class, string $panelId): string
    {
        if (is_subclass_of($class, DescribesForAi::class)) {
            return trim($class::aiDescription());
        }

        $text = app(OnboardingManager::class)->toursFor($class, $panelId)
            ->flatMap(static fn (Tour $tour): array => $tour->steps())
            ->filter(static fn (TourStep $step): bool => $step->isVisible())
            ->map(static function (TourStep $step): string {
                $popover = (array) ($step->toArray()['popover'] ?? []);
                $title = trim((string) ($popover['title'] ?? ''));
                $body = trim((string) ($popover['description'] ?? ''));

                return $title === '' ? $body : rtrim($title, '.:').': '.$body;
            })
            ->filter()
            ->implode(' ');

        if ($text !== '') {
            return $text;
        }

        $exemption = ((array) config('fc-onboarding.exempt', []))[$class] ?? null;

        return is_string($exemption) ? __($exemption) : '';
    }

    // ═══════════════════════ الشاشات ═══════════════════════

    /**
     * @param class-string<FilamentResource> $resource
     * @return array<string, mixed>|null
     */
    private function resourceScreen(string $resource, Panel $panel): ?array
    {
        try {
            $model = $resource::getModel();
            $user = $this->session->user();
            $accessible = $user !== null && Gate::forUser($user)->allows('viewAny', $model);

            $pages = [];

            foreach ($resource::getPages() as $name => $registration) {
                $pages[$name] = $this->pagePath($resource, (string) $name, $panel);
            }

            return [
                'key' => Str::kebab(Str::beforeLast(class_basename($resource), 'Resource')),
                'type' => 'resource',
                'title' => $resource::getNavigationLabel(),
                'record_label' => $resource::getModelLabel(),
                'group' => $this->group($resource::getNavigationGroup()),
                'url' => $resource::hasPage('index') ? $resource::getUrl('index', panel: $panel->getId()) : null,
                'record_pages' => array_filter($pages),
                'description' => $this->describeResource($resource, $panel->getId()),
                'tools' => $this->toolsFor($resource, $panel->getId()),
                'accessible' => $accessible,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @param class-string<Page> $page
     * @return array<string, mixed>|null
     */
    private function pageScreen(string $page, Panel $panel): ?array
    {
        try {
            $cluster = $page::getCluster();

            return [
                'key' => Str::kebab(class_basename($page)),
                'type' => $cluster !== null ? 'tab' : 'page',
                'title' => $page::getNavigationLabel(),
                'group' => $this->group($cluster !== null ? $cluster::getNavigationGroup() : $page::getNavigationGroup()),
                'section' => $cluster !== null ? $cluster::getNavigationLabel() : null,
                'url' => $page::getUrl(panel: $panel->getId()),
                'description' => $this->describe($page, $panel->getId()),
                'tools' => [],
                'accessible' => $page::canAccess(),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /** @return list<class-string<Page>> */
    private function pageClasses(Panel $panel): array
    {
        $pages = array_filter(
            [...$panel->getPages(), $panel->getTenantProfilePage(), $panel->getProfilePage()],
            static fn (mixed $page): bool => is_string($page)
                && class_exists($page)
                && is_subclass_of($page, Page::class)
                && ! is_subclass_of($page, Cluster::class),
        );

        return array_values(array_unique($pages));
    }

    /** مسار صفحة سجل كقالب: `/admin/t/acme/announcements/{id}/edit` */
    private function pagePath(string $resource, string $name, Panel $panel): ?string
    {
        if ($name === 'index') {
            return null;
        }

        // صفحات السجل محتاجة `{record}` — والباقي (إنشاء) مابياخدش
        foreach ([['record' => '__ID__'], []] as $parameters) {
            try {
                return str_replace('__ID__', '{id}', $resource::getUrl($name, $parameters, panel: $panel->getId()));
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function toolsFor(string $resource, string $panelId): array
    {
        $declaration = $this->registry->declarationFor($resource, $panelId);

        if ($declaration === null) {
            return [];
        }

        return array_map(
            static fn (ResourceTool $tool): string => $tool->name(),
            app(McpToolFactory::class)->registeredFor($declaration),
        );
    }

    /** @param class-string<FilamentResource> $resource */
    private function indexPage(string $resource): ?string
    {
        $index = $resource::getPages()['index'] ?? null;

        return $index?->getPage();
    }

    private function group(mixed $group): ?string
    {
        return match (true) {
            $group === null => null,
            $group instanceof \UnitEnum && method_exists($group, 'getLabel') => (string) $group->getLabel(),
            default => (string) $group,
        };
    }
}
