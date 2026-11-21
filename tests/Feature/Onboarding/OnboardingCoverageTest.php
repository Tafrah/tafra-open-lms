<?php

declare(strict_types=1);

use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use FutureCode\Onboarding\Facades\Onboarding;
use FutureCode\Onboarding\Tours\Tour;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| عقد الأونبوردنج لكل ميزة (docs/15 بند ١٠)
|--------------------------------------------------------------------------
| إجباري لكل شاشة في اللوحتين: جولة (أو إعفاء بسبب مترجم)، وكل مورد
| ليه حالة فاضية خاصة بيه مش الافتراضية. ميزة جديدة من غير أونبوردنج =
| الاختبار ده أحمر.
*/

/**
 * كل شاشة في اللوحة: صفحة قائمة كل مورد + كل صفحة مسجّلة.
 *
 * @return array<string, string> [class => label]
 */
function onboardingScreens(string $panelId): array
{
    $panel = Filament::getPanel($panelId);
    $screens = [];

    foreach ($panel->getResources() as $resource) {
        /** @var class-string<resource> $resource */
        $index = $resource::getPages()['index'] ?? null;

        if ($index !== null) {
            $screens[$index->getPage()] = class_basename($resource);
        }
    }

    foreach ($panel->getPages() as $page) {
        // الـ cluster مش شاشة: بيحوّل لأول تبويب، والتبويبات نفسها ليها جولتها
        if (is_subclass_of($page, Cluster::class)) {
            continue;
        }

        $screens[$page] = class_basename($page);
    }

    // صفحات الملف بتتسجّل برا `getPages()` (docs/21 بند ٣٠)
    foreach (array_filter([$panel->getTenantProfilePage(), $panel->getProfilePage()]) as $page) {
        $screens[$page] = class_basename($page);
    }

    return $screens;
}

it('كل شاشة ليها جولة أو إعفاء مكتوب', function (string $panelId): void {
    $exempt = (array) config('fc-onboarding.exempt', []);
    $missing = [];

    foreach (onboardingScreens($panelId) as $class => $label) {
        if (array_key_exists($class, $exempt)) {
            continue;
        }

        if (Onboarding::toursFor($class, $panelId)->isEmpty()) {
            $missing[] = "{$label} ({$class})";
        }
    }

    expect($missing)->toBeEmpty(
        "شاشات من غير جولة في لوحة {$panelId} — اعمل `Presentation/Tours/*Tour.php` أو إعفاء بسبب:\n".implode("\n", $missing),
    );
})->with(['admin', 'central']);

it('كل إعفاء ليه سبب مترجم بكل اللغات', function (): void {
    $problems = [];

    foreach ((array) config('fc-onboarding.exempt', []) as $class => $reason) {
        foreach ((array) config('app.supported_locales') as $locale) {
            if (! is_string($reason) || __($reason, [], $locale) === $reason) {
                $problems[] = "{$class} ({$locale})";
            }
        }
    }

    expect($problems)->toBeEmpty("إعفاءات من غير سبب مترجم:\n".implode("\n", $problems));
});

it('كل جولة خمس خطوات أو أقل ونصوصها مترجمة', function (string $locale): void {
    actingAsRole('super_admin');
    app()->setLocale($locale);

    $problems = [];

    foreach (Onboarding::tours() as $tour) {
        $steps = $tour->steps();

        if (count($steps) > Tour::MAX_STEPS) {
            $problems[] = $tour::key().': '.count($steps).' خطوات';
        }

        foreach ($steps as $step) {
            $popover = $step->toArray()['popover'];

            foreach (['title', 'description'] as $field) {
                if (blank($popover[$field]) || str_contains((string) $popover[$field], '::')) {
                    $problems[] = $tour::key().".{$step->key}.{$field}: ".$popover[$field];
                }
            }
        }

        foreach ($tour::panels() as $panelId) {
            foreach ($tour::pages() as $page) {
                if (! array_key_exists($page, onboardingScreens($panelId))) {
                    $problems[] = $tour::key().": الصفحة {$page} مش مسجّلة في لوحة {$panelId}";
                }
            }
        }
    }

    expect($problems)->toBeEmpty("جولات مخالفة ({$locale}):\n".implode("\n", $problems));
})->with(['ar', 'en']);

it('كل مورد ليه حالة فاضية خاصة بيه', function (string $panelId): void {
    $panelId === 'central' ? actingAsCentral('super_admin') : actingAsRole('super_admin');

    $generic = __('table.empty.heading');
    $problems = [];

    foreach (Filament::getPanel($panelId)->getResources() as $resource) {
        /** @var class-string<resource> $resource */
        $index = $resource::getPages()['index'] ?? null;

        if ($index === null) {
            continue;
        }

        $table = Livewire::test($index->getPage())->instance()->getTable();

        if (blank($table->getEmptyStateHeading()) || $table->getEmptyStateHeading() === $generic) {
            $problems[] = class_basename($resource).': العنوان';
        }

        if (blank($table->getEmptyStateDescription()) || $table->getEmptyStateDescription() === __('table.empty.description')) {
            $problems[] = class_basename($resource).': الوصف';
        }
    }

    expect($problems)->toBeEmpty("موارد بالحالة الفاضية الافتراضية ({$panelId}):\n".implode("\n", $problems));
})->with(['admin', 'central']);
