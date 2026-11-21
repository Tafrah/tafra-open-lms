<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Src\Contexts\Tenancy\Domain\Models\Tenant;

/*
|--------------------------------------------------------------------------
| «مفيش عنصر ميت» — أي عنصر ظاهر، المستخدم يقدر يدخله فعلاً
|--------------------------------------------------------------------------
| docs/07 بند ١٠ · وde facto جولة اختراق آلية: كل مسار بيتجرّب
| بدور أقل كمان، ولازم يرفض اللي مش من حقه. في اللوحتين (docs/24).
*/

/** @return array<int, array{0: string, 1: string}> [label, url] */
function reachablePages(string $panelId = 'admin'): array
{
    $panel = Filament::getPanel($panelId);
    $urls = [];

    foreach ($panel->getPages() as $page) {
        /** @var class-string<Page> $page */
        if (! $page::canAccess()) {
            continue;
        }

        $urls[] = [class_basename($page), $page::getUrl(panel: $panelId)];
    }

    foreach ($panel->getResources() as $resource) {
        /** @var class-string<resource> $resource */
        if (! $resource::canViewAny()) {
            continue;
        }

        foreach (array_keys($resource::getPages()) as $pageName) {
            // الصفحات اللي محتاجة سجل بتتجرّب في اختبارات المورد نفسه
            if (in_array($pageName, ['view', 'edit'], true)) {
                continue;
            }

            if ($pageName === 'create' && ! $resource::canCreate()) {
                continue;
            }

            $urls[] = [class_basename($resource).":{$pageName}", $resource::getUrl($pageName, panel: $panelId)];
        }
    }

    return $urls;
}

/** @return list<string> روابط الـ clusters — بتحوّل لأول تبويب مسموح بدل ما تعرض */
function clusterUrls(string $panelId = 'admin'): array
{
    return array_values(array_map(
        static fn (string $cluster): string => $cluster::getUrl(panel: $panelId),
        Filament::getPanel($panelId)->getClusters(),
    ));
}

/** الصفحة بتفتح = 200، والـ cluster بس مسموحله 302 (docs/21 بند ٢٩) */
function opens(string $url, int $status, string $panelId = 'admin'): bool
{
    return $status === 200 || ($status === 302 && in_array($url, clusterUrls($panelId), true));
}

it('كل عنصر ظاهر بيفتح فعلاً — لوحة المؤسسة', function (string $role): void {
    actingAsRole($role);

    $broken = [];

    foreach (reachablePages() as [$label, $url]) {
        $status = $this->get($url)->getStatusCode();

        if (! opens($url, $status)) {
            $broken[] = "{$label} ({$url}) → {$status}";
        }
    }

    expect($broken)->toBeEmpty("عناصر ظاهرة ومش بتفتح:\n".implode("\n", $broken));
})->with(['super_admin', 'admin', 'viewer']);

it('كل عنصر ظاهر بيفتح فعلاً — اللوحة المركزية', function (string $role): void {
    actingAsCentral($role);

    $pages = reachablePages('central');
    expect($pages)->not->toBeEmpty();

    $broken = [];

    foreach ($pages as [$label, $url]) {
        $status = $this->get($url)->getStatusCode();

        if (! opens($url, $status, 'central')) {
            $broken[] = "{$label} ({$url}) → {$status}";
        }
    }

    expect($broken)->toBeEmpty("عناصر ظاهرة ومش بتفتح:\n".implode("\n", $broken));
})->with(['super_admin', 'support']);

/*
|--------------------------------------------------------------------------
| المكافئ الآلي لاختبار الاختراق اليدوي (docs/20 بند ١٥)
|--------------------------------------------------------------------------
*/

it('يمنع المشاهد من كل مسار إداري بالرابط المباشر', function (): void {
    // بنجمع المسارات وهو مدير عام، وبعدين نجرّبها بدور المشاهد
    actingAsRole('super_admin');
    $privileged = collect(reachablePages())
        ->filter(fn (array $page): bool => in_array($page[1], clusterUrls(), true)
            || str_contains($page[1], '/roles')
            || str_contains($page[1], '/activity-logs')
            || str_contains($page[1], '/onboarding-analytics')
            || str_ends_with($page[1], '/create'))
        ->all();

    expect($privileged)->not->toBeEmpty('لازم يكون فيه مسارات إدارية نجرّبها');

    actingAsRole('viewer');

    $leaked = [];

    foreach ($privileged as [$label, $url]) {
        $status = $this->get($url)->getStatusCode();

        if (! in_array($status, [403, 404], true)) {
            $leaked[] = "{$label} ({$url}) → {$status}";
        }
    }

    expect($leaked)->toBeEmpty("مسارات إدارية مفتوحة للمشاهد:\n".implode("\n", $leaked));
});

it('يمنع مدير المؤسسة من كل مسار في اللوحة المركزية', function (): void {
    actingAsCentral('super_admin');
    $central = [...reachablePages('central'), ...array_map(
        static fn (string $url): array => ['cluster', $url],
        clusterUrls('central'),
    )];

    expect($central)->not->toBeEmpty();

    actingAsRole('admin');

    $leaked = [];

    foreach ($central as [$label, $url]) {
        $status = $this->get($url)->getStatusCode();

        if ($status !== 403) {
            $leaked[] = "{$label} ({$url}) → {$status}";
        }
    }

    expect($leaked)->toBeEmpty("مسارات مركزية مفتوحة لمدير مؤسسة:\n".implode("\n", $leaked));
});

it('يمنع الوصول لمؤسسة المستخدم مش عضو فيها', function (): void {
    actingAsRole('admin');

    $other = Tenant::factory()->create();

    $this->get('/admin/t/'.$other->slug)->assertNotFound();
});
