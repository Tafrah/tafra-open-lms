<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\File;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Presentation\Filament\Pages\EditOrganization;

/**
 * أخطر نوع خطأ ترجمة: الصفحة بترجّع 200 وبتعرض **المفتاح** بدل النص.
 *
 * حصل فعلاً: تسميات اللوحة بتتقيّم وقت بناء اللوحة، واللي بيحصل في
 * `boot()` بتاع Filament — **قبل** مزوّدي السياقات ما يسجّلوا namespace
 * الترجمة. الـ Translator بيخزّن النتيجة الفاضية للطلب كله، فكل اللوحة
 * بتطلع مفاتيح خام وكل الاختبارات تفضل خضرا (docs/21 بند ١٨).
 *
 * الاختبار ده بيرندر الشاشات فعلاً في **اللوحتين** وبيدوّر على مفاتيحنا في الـ HTML.
 */

/** @return list<string> أسماء namespaces الترجمة بتاعتنا */
function ourTranslationNamespaces(): array
{
    $namespaces = ['fc-onboarding'];

    foreach (File::directories(src_path('Contexts')) as $context) {
        if (File::isDirectory($context.'/Lang')) {
            $namespaces[] = strtolower(basename($context));
        }
    }

    return $namespaces;
}

/** @return list<string> المفاتيح الخام اللي ظهرت في الـ HTML */
function leakedTranslationKeys(string $html): array
{
    // مفتاح ترجمة حقيقي شكله `ns::group.item` — لازم يكون فيه نقطة.
    // من غير الشرط ده بنمسك `wire:key` بتاع Filament (زي
    // `application-identity::data::section`) كأنه مفتاح مسرّب.
    $pattern = '/(?<![\w\-])('.implode('|', array_map(
        static fn (string $ns): string => preg_quote($ns, '/'),
        ourTranslationNamespaces(),
    )).')::[a-z0-9_]+\.[a-z0-9_.]+/i';

    preg_match_all($pattern, $html, $found);

    return array_values(array_unique($found[0]));
}

/** بيسجّل الدخول كمدير عام على اللوحة المطلوبة. */
function actingAsSuperAdminOn(string $panelId): User
{
    return $panelId === 'central' ? actingAsCentral('super_admin') : actingAsRole('super_admin');
}

it('لا تظهر مفاتيح ترجمة خام في أي شاشة', function (string $panelId, string $locale): void {
    $user = actingAsSuperAdminOn($panelId);
    $user->forceFill(['locale' => $locale])->save();

    $panel = Filament::getPanel($panelId);
    $urls = [];

    foreach ($panel->getPages() as $page) {
        /** @var class-string<Page> $page */
        if ($page::canAccess()) {
            $urls[class_basename($page)] = $page::getUrl(panel: $panelId);
        }
    }

    foreach ($panel->getResources() as $resource) {
        /** @var class-string<resource> $resource */
        if ($resource::canViewAny()) {
            $urls[class_basename($resource)] = $resource::getUrl('index', panel: $panelId);
        }
    }

    // صفحات مش بتتكتشف (`$isDiscovered = false`) ومش في `getPages()`
    if ($panelId === 'admin') {
        $urls['EditOrganization'] = EditOrganization::getUrl(tenant: currentTenant());
    }

    $leaks = [];

    foreach ($urls as $label => $url) {
        $html = $this->get($url)->getContent();

        foreach (leakedTranslationKeys((string) $html) as $key) {
            $leaks[] = "{$label}: {$key}";
        }
    }

    expect($leaks)->toBeEmpty(
        "مفاتيح ترجمة خام ظهرت للمستخدم ({$panelId}/{$locale}):\n".implode("\n", array_slice($leaks, 0, 25)),
    );
})->with(['admin', 'central'])->with(['ar', 'en']);

it('تسميات الموارد والصفحات مترجمة مش مفاتيح', function (string $panelId): void {
    actingAsSuperAdminOn($panelId);

    $unresolved = [];
    $panel = Filament::getPanel($panelId);

    foreach ($panel->getResources() as $resource) {
        /** @var class-string<resource> $resource */
        foreach (['getModelLabel', 'getPluralModelLabel'] as $method) {
            $label = $resource::{$method}();

            if (str_contains($label, '::')) {
                $unresolved[] = class_basename($resource)."::{$method}() → {$label}";
            }
        }
    }

    foreach ($panel->getPages() as $page) {
        /** @var class-string<Page> $page */
        $label = $page::getNavigationLabel();

        if (str_contains($label, '::')) {
            $unresolved[] = class_basename($page).'::getNavigationLabel() → '.$label;
        }
    }

    expect($unresolved)->toBeEmpty("تسميات غير مترجمة:\n".implode("\n", $unresolved));
})->with(['admin', 'central']);
