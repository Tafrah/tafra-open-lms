<?php

declare(strict_types=1);
use Src\Support\Domain\Authorization\Policy;

/*
|--------------------------------------------------------------------------
| الاختبارات المعمارية — بتمنع كسر البنية من غير اختبار لكل كلاس
|--------------------------------------------------------------------------
| بتجري في ثواني وبتمسك ٨٠٪ من كسر القواعد (docs/17 بند ٣).
*/

arch('طبقة Domain نظيفة من الإطار')
    ->expect('Src\Contexts')
    ->not->toUse([
        'Filament\Resources',
        'Filament\Tables',
        'Livewire\Component',
    ])
    ->ignoring([
        'Src\Contexts\Identity\Presentation',
        'Src\Contexts\Tenancy\Presentation',
        'Src\Contexts\Content\Presentation',
        'Src\Contexts\Media\Presentation',
        'Src\Contexts\Notifications\Presentation',
        'Src\Contexts\Settings\Presentation',
        'Src\Contexts\Audit\Presentation',
        'Src\Contexts\Ai\Presentation',
        'Src\Contexts\Academy\Presentation',
        'Src\Contexts\Exams\Presentation',
        'Src\Contexts\Students\Presentation',
        'Src\Contexts\Billing\Presentation',
        'Src\Contexts\Storefront\Presentation',
        'Src\Contexts\Installer\Presentation',
    ]);

// ملاحظة: الـ wildcard هنا مقصود — القواعد دي كانت مكتوبة لسياقين
// بالاسم، يعني أي سياق جديد كان بيعدّي من غير فحص.
arch('طبقة Application لا تعرف الواجهة')
    ->expect('Src\Contexts\*\Application')
    ->not->toUse(['Filament\Resources', 'Filament\Tables', 'Livewire']);

arch('الـ Actions نهائية وغير قابلة للتغيير')
    ->expect('Src\Contexts\*\Application\Actions')
    ->toBeFinal()
    ->toBeReadonly();

arch('لا أدوات تصحيح متروكة')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('كل السياسات ترث الكلاس الأساسي')
    ->expect('Src\Contexts\*\Infrastructure\Policies')
    ->toExtend(Policy::class);

it('لا تستخدم env() خارج ملفات config', function (): void {
    // `bootstrap/` مشمولة عن قصد: `env()` هناك بترجّع null مع config:cache،
    // وده بيحوّل إعداد أمني (TRUSTED_PROXIES) لقيمة مفتوحة بصمت.
    $violations = grepAll('/(?<![\w:>$])env\s*\(/', [src_path(), app_path(), base_path('bootstrap')]);

    expect($violations)->toBeEmpty(
        "استدعاء env() بره config — بيرجّع null مع config:cache:\n".implode("\n", $violations),
    );
});

it('كل ملفات PHP فيها declare(strict_types=1)', function (): void {
    $violations = [];

    foreach (allPhpFiles([src_path(), app_path()]) as $file) {
        if (! str_contains($file->getContents(), 'declare(strict_types=1)')) {
            $violations[] = $file->getRelativePathname();
        }
    }

    expect($violations)->toBeEmpty("ملفات من غير strict_types:\n".implode("\n", $violations));
});

it('السياقات لا تستورد موديلات بعض إلا عبر النقاط المسموحة', function (): void {
    // Identity هو السياق الأساسي — باقي السياقات بتشاور على User في
    // العلاقات والسياسات، ودي النقطة العامة الوحيدة المسموح بيها.
    $violations = [];

    foreach (allPhpFiles([src_path('Contexts')]) as $file) {
        $contents = $file->getContents();
        $path = $file->getPathname();

        if (! str_contains($path, '/Contexts/Identity/')) {
            continue;
        }

        // Identity ما يعرفش موديلات Content
        if (preg_match('/use Src\\\\Contexts\\\\Content\\\\Domain\\\\Models/', phpCodeWithoutComments($contents))) {
            $violations[] = $file->getRelativePathname();
        }
    }

    expect($violations)->toBeEmpty(
        "Identity بيستورد موديلات Content — استخدم حدث:\n".implode("\n", $violations),
    );
});

/**
 * شرط قبول `docs/01`: **السياق مسجّل في `bootstrap/providers.php` وفي
 * `databaseMigrationsPath` بملف `phpstan.neon`.**
 *
 * القائمتين دول بتتكتبوا بالإيد، فبيتنسوا. وقت ما اتفحصوا كان
 * `Media` و`Audit` ناقصين من `phpstan.neon` — يعني larastan مكانش شايف
 * سكيما الجداول بتاعتهم، والتحليل كان بيعدّي على مستوى ٦ وهو أعمى عن
 * جزء من الموديلات. الفشل صامت: صفر أخطاء، وتغطية أقل (docs/21 بند ٢٤).
 */
it('كل سياق مسجّل في مزوّدي التطبيق وفي مسارات larastan', function (): void {
    $contexts = array_map(
        static fn (string $path): string => basename($path),
        File::directories(src_path('Contexts')),
    );

    $providers = (string) File::get(base_path('bootstrap/providers.php'));
    $phpstan = (string) File::get(base_path('phpstan.neon'));

    $problems = [];

    foreach ($contexts as $context) {
        if (! str_contains($providers, "Src\\Contexts\\{$context}\\{$context}ServiceProvider")) {
            $problems[] = "{$context}: مش مسجّل في bootstrap/providers.php";
        }

        $migrations = "src/Contexts/{$context}/Database/Migrations";

        if (File::isDirectory(base_path($migrations)) && ! str_contains($phpstan, $migrations)) {
            $problems[] = "{$context}: {$migrations} مش في databaseMigrationsPath بملف phpstan.neon";
        }
    }

    expect($problems)->toBeEmpty("سياقات ناقصة من التسجيل:\n".implode("\n", $problems));
});

/**
 * كل مسار مكتوب في `phpstan.neon` لازم يكون موجود **وقت الاستنساخ**.
 *
 * `database/factories` كان في `paths:` وهو مجلد فاضي — وgit مابيتتبّعش
 * المجلدات الفاضية. النتيجة: التحليل بيعدّي محلياً وبيموت على أول CI
 * بـ «Path ... does not exist». الفشل ماكانش ممكن يبان غير على نسخة نضيفة.
 */
it('كل مسار في phpstan.neon موجود ومتتبَّع', function (): void {
    $neon = (string) File::get(base_path('phpstan.neon'));

    preg_match_all('/^\s+- ([A-Za-z0-9_\-\/*.]+)$/m', $neon, $matches);

    $problems = [];
    $checked = 0;

    foreach ($matches[1] as $path) {
        // الأسطر اللي بتشاور على ملفات vendor أو فيها wildcard مش مجلدات ثابتة
        if (str_starts_with($path, 'vendor/') || str_contains($path, '*')) {
            continue;
        }

        $absolute = base_path($path);
        $checked++;

        if (! File::exists($absolute)) {
            $problems[] = "{$path}: مش موجود";

            continue;
        }

        if (File::isDirectory($absolute) && trackedFilesIn($path) === 0) {
            $problems[] = "{$path}: موجود محلياً بس مفيهوش ملف متتبَّع — هيختفي وقت الاستنساخ";
        }
    }

    expect($checked)->toBeGreaterThan(5, 'الفحص مالقاش المسارات — يبقى النمط اتكسر');
    expect($problems)->toBeEmpty("مسارات phpstan بايظة:\n".implode("\n", $problems));
});

/** عدد الملفات اللي git متتبّعها تحت المسار ده (بيرجع عدد الملفات لو مفيش git). */
function trackedFilesIn(string $path): int
{
    $output = [];
    $status = 0;

    exec('git -C '.escapeshellarg(base_path()).' ls-files -- '.escapeshellarg($path).' 2>/dev/null', $output, $status);

    if ($status !== 0) {
        return count(File::allFiles(base_path($path)));
    }

    return count(array_filter($output));
}

/**
 * مفيش مجلد فاضي في شجرة العمل.
 *
 * git مابيتتبّعش المجلدات الفاضية، فأي مجلد فاضي عندك **مش موجود** عند
 * أي حد بيستنسخ المشروع. النتيجة: البنية اللي في `docs/01` مطابقة على
 * جهازك ومختلفة على نسخة نضيفة، وكونفيج بيشاور على مسار مش موجود
 * بيكسر الـ CI بس (docs/21 بند ٢٦).
 *
 * لو محتاج مجلد يفضل موجود وهو فاضي، حط فيه `.gitkeep`.
 */
it('مفيش مجلد فاضي مش متتبَّع', function (): void {
    $roots = ['app', 'src', 'tests', 'database', 'config', 'routes', 'resources', 'packages', 'deploy', 'docker'];
    $empty = [];

    foreach ($roots as $root) {
        $path = base_path($root);

        if (! File::isDirectory($path)) {
            continue;
        }

        foreach ([$path, ...allDirectoriesIn($path)] as $directory) {
            // hidden: true عشان `.gitkeep` يتحسب — هو بالظبط الطريقة
            // المقصودة إن مجلد فاضي يفضل موجود في المستودع
            if (File::files($directory, hidden: true) === [] && File::directories($directory) === []) {
                $empty[] = str_replace(base_path().'/', '', $directory);
            }
        }
    }

    expect($empty)->toBeEmpty(
        "مجلدات فاضية هتختفي وقت الاستنساخ (حط .gitkeep أو احذفها):\n".implode("\n", $empty),
    );
});

/** @return list<string> كل المجلدات تحت المسار ده بشكل متكرر */
function allDirectoriesIn(string $path): array
{
    $found = [];

    foreach (File::directories($path) as $directory) {
        $found[] = $directory;
        $found = [...$found, ...allDirectoriesIn($directory)];
    }

    return $found;
}

/**
 * كل موديل Eloquent معرّف في `Domain/Models` بتاع سياقه (docs/17 بند ٣).
 *
 * الوثيقة بتكتبها كـ `expect(Model::class)->toOnlyBeUsedIn([...Domain, ...Infrastructure])`،
 * وده **مش قابل للتحقيق**: الصيغة دي بتفحص مكان الـ **استخدام**، والـ
 * wildcard مابيشتغلش فيها — فبتفشل على موديلات هي نفسها في `Domain`.
 * القاعدة اللي بتهم فعلاً هي مكان **التعريف**: لو موديل اتكتب في
 * `Presentation` أو `Application`، البنية اتكسرت.
 */
it('كل موديل Eloquent معرّف في Domain/Models', function (): void {
    $violations = [];

    foreach (allPhpFiles([src_path()]) as $file) {
        $path = $file->getPathname();

        if (! preg_match('/^\s*(final\s+)?(abstract\s+)?class\s+(\w+)\s+extends\s+(\w+)/m', $file->getContents(), $found)) {
            continue;
        }

        // بنعتمد على الوراثة المباشرة من Model أو Authenticatable
        if (! in_array($found[4], ['Model', 'Authenticatable', 'SpatieActivity', 'Pivot'], true)) {
            continue;
        }

        if (! str_contains($path, '/Domain/Models/')) {
            $violations[] = $file->getRelativePathname();
        }
    }

    expect($violations)->toBeEmpty("موديلات بره Domain/Models:\n".implode("\n", $violations));
});
