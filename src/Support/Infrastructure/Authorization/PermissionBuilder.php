<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Authorization;

use Illuminate\Support\Arr;

/**
 * المترجم بين `config/authorization.php` وأسماء الصلاحيات الفعلية.
 *
 * الكونفيج هو المصدر الوحيد للحقيقة — مفيش `Permission::create()` في أي سيدر.
 */
final class PermissionBuilder
{
    /** @var array<string, list<string>>|null */
    private ?array $cachedResourcePermissions = null;

    /**
     * كل أسماء الصلاحيات النهائية: الموارد + الصفحات + الودجتس.
     *
     * @return list<string>
     */
    public function allPermissionNames(): array
    {
        return array_values(array_unique([
            ...Arr::flatten($this->resourcePermissions()),
            ...$this->pageAbilities(),
            ...$this->widgetAbilities(),
        ]));
    }

    /**
     * صلاحيات كل مورد على حدة.
     *
     * @return array<string, list<string>>
     */
    public function resourcePermissions(): array
    {
        if ($this->cachedResourcePermissions !== null) {
            return $this->cachedResourcePermissions;
        }

        $separator = $this->separator();
        $defaults = (array) config('authorization.default_actions', []);
        $result = [];

        foreach ((array) config('authorization.resources', []) as $resource => $definition) {
            $actions = $definition['actions'] ?? null;
            $actions = is_array($actions) ? $actions : $defaults;
            $actions = [...$actions, ...($definition['extra'] ?? [])];

            $result[$resource] = array_values(array_unique(array_map(
                static fn (string $action): string => $action.$separator.$resource,
                $actions,
            )));
        }

        return $this->cachedResourcePermissions = $result;
    }

    /** @return list<string> */
    public function pageAbilities(): array
    {
        return array_keys((array) config('authorization.pages', []));
    }

    /** @return list<string> */
    public function widgetAbilities(): array
    {
        return array_keys((array) config('authorization.widgets', []));
    }

    /** @return list<string> */
    public function resourceNames(): array
    {
        return array_keys((array) config('authorization.resources', []));
    }

    /**
     * كل الأفعال المستخدمة فعلياً عبر كل الموارد.
     *
     * @return list<string>
     */
    public function actionNames(): array
    {
        $separator = $this->separator();
        $actions = [];

        foreach ($this->resourcePermissions() as $permissions) {
            foreach ($permissions as $permission) {
                $actions[] = explode($separator, $permission, 2)[0];
            }
        }

        return array_values(array_unique($actions));
    }

    /**
     * يفكّ أنماط الأدوار لأسماء صلاحيات نهائية.
     *
     * `*`             → كل حاجة
     * `users.*`       → كل أفعال المورد users
     * `view_any.*`    → الفعل view_any على كل الموارد
     * `widget.*`      → كل الودجتس · `access.*` → كل الصفحات
     * `view.users`    → نفسها
     *
     * @param list<string> $patterns
     * @return list<string>
     */
    public function expandPatterns(array $patterns): array
    {
        $separator = $this->separator();
        $all = $this->allPermissionNames();
        $expanded = [];

        foreach ($patterns as $pattern) {
            if ($pattern === '*') {
                return $all;
            }

            if (! str_contains($pattern, $separator)) {
                $expanded[] = $pattern;

                continue;
            }

            [$left, $right] = explode($separator, $pattern, 2);

            if ($right !== '*') {
                $expanded[] = $pattern;

                continue;
            }

            $expanded = [...$expanded, ...$this->expandLeftWildcard($left, $separator)];
        }

        // نرجّع الموجود فعلاً في الكتالوج بس — عشان خطأ مطبعي ما يخلقش صلاحية وهمية
        return array_values(array_intersect($all, array_unique($expanded)));
    }

    /**
     * شجرة الصلاحيات مجمّعة للعرض في شاشة الأدوار.
     *
     * @return array<string, array<string, list<string>>> [group => [resource|'_pages' => permissions]]
     */
    public function groups(): array
    {
        $groups = [];

        foreach ((array) config('authorization.resources', []) as $resource => $definition) {
            $group = $definition['group'] ?? 'system';
            $groups[$group][$resource] = $this->resourcePermissions()[$resource] ?? [];
        }

        foreach ((array) config('authorization.pages', []) as $ability => $group) {
            $groups[$group]['_pages'][] = $ability;
        }

        foreach ((array) config('authorization.widgets', []) as $ability => $group) {
            $groups[$group]['_widgets'][] = $ability;
        }

        return $groups;
    }

    /** @return list<string> */
    private function expandLeftWildcard(string $left, string $separator): array
    {
        // مورد؟ → كل أفعاله
        $resourcePermissions = $this->resourcePermissions();

        if (isset($resourcePermissions[$left])) {
            return $resourcePermissions[$left];
        }

        // بادئة صفحة أو ودجت؟
        $prefixed = array_values(array_filter(
            [...$this->pageAbilities(), ...$this->widgetAbilities()],
            static fn (string $ability): bool => str_starts_with($ability, $left.$separator),
        ));

        if ($prefixed !== []) {
            return $prefixed;
        }

        // فعل؟ → على كل الموارد اللي بتدعمه
        return array_values(array_filter(
            Arr::flatten($resourcePermissions),
            static fn (string $permission): bool => str_starts_with($permission, $left.$separator),
        ));
    }

    private function separator(): string
    {
        return (string) config('authorization.separator', '.');
    }
}
