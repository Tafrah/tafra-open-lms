<?php

declare(strict_types=1);

use Filament\Resources\Resource;
use FutureCode\Onboarding\Facades\Onboarding;

/*
|--------------------------------------------------------------------------
| مساعدات رحلات المستخدم (docs/17 بند ١٣)
|--------------------------------------------------------------------------
| كل شاشة بتمر بنفس الحد الأدنى: الوصول من السايدبار أو قائمة المستخدم،
| الجولة بتبدأ وبتتقفل وماترجعش، الواجهة سليمة بلغتين وعلى موبايل وداكن،
| والدور الممنوع مالوش رابط ولا صفحة. الملفات بتنادي المساعدات دي بدل ما
| تكرر نفس الـ ٣٠ سطر لكل شاشة.
*/

/** مسار الشاشة (من غير الدومين) — المتصفح بيشتغل على سيرفر تاني غير APP_URL. */
function screenPath(string $screen, ?string $panel = null): string
{
    $url = is_subclass_of($screen, Resource::class)
        ? $screen::getUrl('index', panel: $panel)
        : $screen::getUrl(panel: $panel);

    return (string) parse_url($url, PHP_URL_PATH);
}

/** الصفحة الرئيسية للوحة المؤسسة الحالية. */
function adminHome(): string
{
    return '/admin/t/'.currentTenant()->slug;
}

/**
 * رابط الشاشة في السايدبار أو قائمة المستخدم (للصفحات الشخصية).
 *
 * كلاس العنصر مش الحاوية: السايدبار المطوي بيكرر نفس الروابط في dropdown
 * (`fi-dropdown-list-item`)، وقائمة المستخدم بتترندر برا `.fi-user-menu`.
 */
function navLinkSelector(string $path, string $area = 'sidebar'): string
{
    $item = $area === 'sidebar' ? '.fi-sidebar a.fi-sidebar-item-btn' : 'a.fi-dropdown-list-item';

    return "{$item}[href\$=\"{$path}\"]";
}

/**
 * الأقسام اللي بتتفتح من وقت للتاني مطوية افتراضياً (NavigationGroup::folded) —
 * المدرس بيفتح القسم الأول، والرحلة كمان.
 */
function openSidebarGroupOf(object $page, string $linkSelector): void
{
    $opened = $page->script(sprintf(
        '(() => { const g = document.querySelector(%s)?.closest(".fi-sidebar-group.fi-collapsed"); if (! g) return false; g.querySelector(".fi-sidebar-group-btn")?.click(); return true; })()',
        json_encode($linkSelector),
    ));

    if ($opened === true) {
        $page->wait(0.5);
    }
}

/**
 * زرار بالاسم الظاهر بالظبط (زي ما قارئ الشاشة بيشوفه) — أثبت من كلاسات
 * Filament اللي بتتبني في PHP، ومابيطابقش العناصر المخفية.
 */
function buttonNamed(string $label, string $scope = ''): string
{
    $selector = 'internal:role=button[name="'.addslashes($label).'"s]';

    return $scope === '' ? $selector : "{$scope} >> {$selector}";
}

/** عنصر قائمة منسدلة بالاسم — Filament 5.11+ بيدّي عناصر القوايم role=menuitem مش button. */
function menuItemNamed(string $label, string $scope = '.fi-dropdown-panel'): string
{
    return "{$scope} >> ".'internal:role=menuitem[name="'.addslashes($label).'"s]';
}

/** رابط بالاسم الظاهر بالظبط — إجراء فيه `url()` (زي زرار الإنشاء) بيترندر `<a>` مش `<button>`. */
function linkNamed(string $label, string $scope = ''): string
{
    $selector = 'internal:role=link[name="'.addslashes($label).'"s]';

    return $scope === '' ? $selector : "{$scope} >> {$selector}";
}

/** تبويب لغة جوه فورم مترجم (زرار `fi-tabs-item` مش role=button). */
function localeTabSelector(string $locale): string
{
    return '.fi-main .fi-tabs-item:has-text("'.__("common.locales.{$locale}").'")';
}

/**
 * بيملا حقل مترجم في تبويب لغة الـ fallback — ده الحقل الإلزامي في الفورمات
 * المترجمة (`required($locale === config('app.fallback_locale'))`)، مش تبويب
 * العربي اللي بيفتح أول واحد.
 */
function fillTranslatable(object $page, string $field, string $value): object
{
    $locale = (string) config('app.fallback_locale');

    return $page->click(localeTabSelector($locale))
        ->fill("[id\$=\"{$field}.{$locale}\"]:visible", $value);
}

/** تبويب جوه cluster الإعدادات. */
function tabLinkSelector(string $path): string
{
    return "a.fi-tabs-item[href\$=\"{$path}\"]";
}

/** مسار العنصر اللي في السايدبار: الـ cluster لو الشاشة تبويب جواه. */
function navPathFor(string $screen, ?string $panel = null): string
{
    $cluster = is_subclass_of($screen, Resource::class) ? null : $screen::getCluster();

    return $cluster === null ? screenPath($screen, $panel) : screenPath($cluster, $panel);
}

/**
 * الرحلة الكاملة لشاشة: من الصفحة الرئيسية للوحة ← الرابط ← الجولة ← إغلاقها
 * ← الزيارة التانية من غير جولة. بترجع الصفحة بعد الزيارة التانية.
 */
function journeyToScreen(string $homePath, string $path, string $tourKey, string $area = 'sidebar', string $locale = 'ar'): object
{
    $home = visit($homePath);
    $home->assertPresent(navLinkSelector($path, $area));

    if ($area === 'sidebar') {
        openSidebarGroupOf($home, navLinkSelector($path, $area));
        $home->click(navLinkSelector($path, $area));
    } else {
        // قائمة المستخدم منسدلة — الرابط موجود في الـ DOM وبنفتحه بالرابط نفسه
        $home->navigate($path);
    }

    $home->assertPathBeginsWith($path)
        ->assertVisible('.driver-popover');

    assertUiQuality($home, $locale);

    $home->click('.driver-popover-close-btn');
    $home->wait(1);

    expect(Onboarding::progress()->fresh()?->hasCompletedTour($tourKey))
        ->toBeTrue("الجولة {$tourKey} ماتسجّلتش بعد الإغلاق");

    $again = visit($path);
    $again->assertMissing('.driver-popover');

    assertUiQuality($again, $locale);

    return $again;
}

/**
 * نفس الشاشة على موبايل بالوضع الداكن: من غير أخطاء، والداكن مطبّق فعلاً،
 * ومفيش سكرول أفقي (أشهر كسر لـ RTL على الشاشات الصغيرة).
 */
function assertMobileDarkQuality(string $path, string $locale = 'ar'): void
{
    $page = visit($path)->on()->mobile()->inDarkMode();

    assertUiQuality($page, $locale);

    $page->assertScript("document.documentElement.classList.contains('dark')", true)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth + 1', true)
        ->assertVisible('.fi-topbar-open-sidebar-btn');
}

/** الدور الممنوع: مفيش رابط في اللوحة، والرابط المباشر مابيعرضش الشاشة. */
function assertScreenDenied(string $homePath, string $path, string $area = 'sidebar', ?string $navPath = null): void
{
    visit($homePath)->assertNotPresent(navLinkSelector($navPath ?? $path, $area));

    // الحالة الفعلية للطلب: 403 أو 404 بس. غياب السايدبار لوحده كان بيعدّي
    // على 500 أو تحويل للدخول — والاتنين مش «ممنوع»
    visit($path)
        ->assertScript("[403, 404].includes(performance.getEntriesByType('navigation')[0].responseStatus)", true)
        ->assertNotPresent('.fi-sidebar')
        ->assertMissing('.driver-popover');
}

/**
 * بيستنى لحد ما شرط على قاعدة البيانات يتحقق — بدل `wait(2)` ثابتة.
 *
 * الحفظ بيعدّي على Livewire والسيرفر؛ تحت ضغط السويت كاملة (خصوصاً multi)
 * الثانيتين مابيكفّوش فالرحلة بتفشل عشوائياً.
 *
 * ⚠️ الانتظار عبر `$page->wait()` مش `usleep()`: سيرفر المتصفح شغّال **جوه نفس
 * العملية**، و`usleep` بيجمّده — الحفظ عمره ما بيخلص والشرط بيفشل كل مرة.
 */
function eventually(object $page, callable $condition, float $seconds = 10.0): mixed
{
    $deadline = microtime(true) + $seconds;

    do {
        $result = $condition();

        if ($result !== null && $result !== false) {
            return $result;
        }

        $page->wait(0.25);
    } while (microtime(true) < $deadline);

    return $condition();
}
