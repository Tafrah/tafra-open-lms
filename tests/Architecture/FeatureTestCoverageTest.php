<?php

declare(strict_types=1);

use Filament\Auth\Pages\EditProfile;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Resources\Resource;
use FutureCode\Onboarding\Pages\OnboardingAnalytics;
use FutureCode\Onboarding\Pages\SetupWizard;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| كل شاشة ليها التلات ملفات: صلاحيات، قبول، رحلة مستخدم (docs/17 بند ١٣)
|--------------------------------------------------------------------------
| الاختبار ده بيعمّم `ResourceCoverageTest` على اللوحتين، والموارد والصفحات.
| الملف بيغطي الشاشة لو بيذكر كلاسها (المورد، أي صفحة من صفحاته، أو الصفحة نفسها)
| — عشان تبويبات بنفس الشكل تتغطى بملف واحد.
*/

/**
 * شاشات مغطّاة في مكان تاني، كل واحدة بسببها.
 *
 * @return array<class-string, string>
 */
function coverageExemptions(): array
{
    return [
        // باكدج الأونبوردنج نفسه — مختبَر في OnboardingTest + OnboardingJourneyTest
        SetupWizard::class => 'onboarding package',
        OnboardingAnalytics::class => 'onboarding package',
        // لوحة المعلومات مجموعة ودجتس، كل ودجت عليه canView() — PanelSmokeTest + SmokeTest
        Dashboard::class => 'widgets container',
        // صفحة الحساب الجاهزة من Filament (اسم/بريد/كلمة مرور) — قواعد كلمة المرور
        // مختبرة في SecurityLifecycleTest، والصفحة نفسها مش كود بتاعنا
        EditProfile::class => 'filament built-in profile',
    ];
}

/**
 * @return array<string, array{label: string, classes: list<string>, resource: class-string|null}>
 */
function coverageScreens(string $panelId): array
{
    $panel = Filament::getPanel($panelId);
    $screens = [];

    foreach ($panel->getResources() as $resource) {
        /** @var class-string<resource> $resource */
        $pages = array_map(static fn ($registration): string => $registration->getPage(), $resource::getPages());

        $screens[$resource] = [
            'label' => Str::beforeLast(class_basename($resource), 'Resource'),
            'classes' => [$resource, ...array_values($pages)],
            'resource' => $resource,
        ];
    }

    foreach ($panel->getPages() as $page) {
        if (is_subclass_of($page, Cluster::class) || array_key_exists($page, coverageExemptions())) {
            continue;
        }

        $screens[$page] = ['label' => class_basename($page), 'classes' => [$page], 'resource' => null];
    }

    // صفحات الملف (`->tenantProfile()` / `->profile()`) بتتسجّل برا `getPages()` —
    // من غير السطرين دول `EditOrganization` عدّى من غير جولة ولا رحلة
    foreach (array_filter([$panel->getTenantProfilePage(), $panel->getProfilePage()]) as $page) {
        if (! array_key_exists($page, coverageExemptions())) {
            $screens[$page] = ['label' => class_basename($page), 'classes' => [$page], 'resource' => null];
        }
    }

    return $screens;
}

/**
 * محتوى كل ملفات الاختبار تحت مجلد (أو glob لمجلدات السياقات).
 *
 * @return list<string>
 */
function coverageFileContents(string $pattern): array
{
    $contents = [];

    foreach (glob(base_path($pattern), GLOB_ONLYDIR) ?: [] as $directory) {
        foreach (File::allFiles($directory) as $file) {
            $contents[] = (string) file_get_contents($file->getPathname());
        }
    }

    return $contents;
}

/** @param list<string> $classes */
function isCoveredBy(array $classes, array $contents): bool
{
    foreach ($contents as $content) {
        foreach ($classes as $class) {
            if (str_contains($content, $class)) {
                return true;
            }
        }
    }

    return false;
}

it('كل شاشة ليها اختبار قبول ورحلة مستخدم وصلاحيات', function (string $panelId): void {
    // القبول ممكن يعيش جنب السياق (`src/Contexts/X/Tests/Acceptance`) زي باقي اختباراته
    $acceptance = [...coverageFileContents('tests/Acceptance'), ...coverageFileContents('src/Contexts/*/Tests/Acceptance')];
    $journeys = coverageFileContents('tests/Browser');
    $missing = [];

    foreach (coverageScreens($panelId) as $screen) {
        if (! isCoveredBy($screen['classes'], $acceptance)) {
            $missing[] = "{$screen['label']} → tests/Acceptance/{$screen['label']}AcceptanceTest.php";
        }

        if (! isCoveredBy($screen['classes'], $journeys)) {
            $folder = Str::studly($panelId);
            $missing[] = "{$screen['label']} → tests/Browser/{$folder}/{$screen['label']}JourneyTest.php";
        }

        if ($screen['resource'] !== null
            && ! File::exists(base_path("tests/Feature/Authorization/{$screen['label']}AuthorizationTest.php"))) {
            $missing[] = "{$screen['label']} → tests/Feature/Authorization/{$screen['label']}AuthorizationTest.php";
        }
    }

    expect($missing)->toBeEmpty("شاشات ناقصة اختبارات ({$panelId}):\n".implode("\n", $missing));
})->with(['admin', 'central']);
