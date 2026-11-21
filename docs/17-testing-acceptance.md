# ١٧ — الاختبارات ومعايير القبول

## الفلسفة

مش بنكتب اختبارات عشان نوصل لنسبة تغطية. بنكتبها عشان **٤ حاجات** ما تكسرش أبداً:

1. **الصلاحيات** — حد شاف حاجة مش من حقه
2. **عزل المستأجرين** — بيانات مؤسسة ظهرت لمؤسسة تانية
3. **البنية** — طبقة كسرت قاعدة الاعتماد
4. **الترجمة** — نص ظهر بالإنجليزي في واجهة عربية

الباقي (المنطق) بنختبره لأنه بيوفّر وقت، مش لأنه إلزامي.

---

## ١. الإعداد

```bash
composer require pestphp/pest --dev --with-all-dependencies
composer require pestphp/pest-plugin-laravel --dev
composer require pestphp/pest-plugin-arch --dev
composer require pestphp/pest-plugin-livewire --dev
php artisan pest:install
```

`phpunit.xml`:

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="pgsql"/>
    <env name="DB_DATABASE" value="fc_admin_testing"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="MAIL_MAILER" value="array"/>
    <env name="PERMISSION_CACHE_STORE" value="array"/>
</php>
```

> **مهم:** الاختبارات على **Postgres مش SQLite**. الفرق في `json`، الفهارس، والقيود بيخلّي اختبار SQLite يكدب عليك.

---

## ٢. المساعدات المشتركة

`tests/Pest.php`:

```php
uses(Tests\TestCase::class, RefreshDatabase::class)->in('Feature', '../src');

function currentTenant(): Tenant
{
    return test()->tenant ??= Tenant::factory()->create();
}

function userWithRole(string $role, ?Tenant $tenant = null): User
{
    $tenant ??= currentTenant();

    app(TenantContext::class)->set($tenant->id);

    return User::factory()
        ->hasAttached($tenant)
        ->create()
        ->assignRole($role);
}

function actingAsRole(string $role): User
{
    $user = userWithRole($role);
    test()->actingAs($user);

    return $user;
}

expect()->extend('toBeForbiddenFor', function (string $role) {
    actingAsRole($role);
    test()->get($this->value)->assertForbidden();

    return $this;
});
```

---

## ٣. الاختبارات المعمارية (Arch)

بتمنع كسر البنية من غير ما تكتب اختبار لكل كلاس.

> اختبارات التفويض المعمارية (منع `hasPermissionTo()` و`can('x.y')` و`skipAuthorization()`) في **`docs/19-policies.md` بند ٩** — كلها إلزامية.

```php
// tests/Architecture/LayersTest.php

arch('طبقة Domain نظيفة من الإطار')
    ->expect('Src\Contexts\*\Domain')
    ->not->toUse([
        'Filament',
        'Livewire',
        'Illuminate\Http',
        'Illuminate\Support\Facades\Request',
    ]);

arch('طبقة Application لا تعرف Filament')
    ->expect('Src\Contexts\*\Application')
    ->not->toUse(['Filament', 'Livewire']);

arch('الـ Actions نهائية وغير قابلة للتغيير')
    ->expect('Src\Contexts\*\Application\Actions')
    ->toBeFinal()
    ->toBeReadonly();

// ملاحظة: الـ wildcard في `expect()` بيشتغل فعلاً — استخدمه بدل ما
// تكتب كل سياق بالاسم، وإلا أي سياق جديد بيعدّي من غير فحص.

// ⚠️ الصيغة دي **مش شغّالة**: `toOnlyBeUsedIn` بتفحص مكان الاستخدام
// والـ wildcard مابيشتغلش فيها، فبتفشل على موديلات هي نفسها في Domain.
// اللي بيهم هو مكان **التعريف** — والاختبار عندنا بيفحصه:
// `it('كل موديل Eloquent معرّف في Domain/Models')` في LayersTest.

arch('لا أدوات تصحيح متروكة')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r', 'die'])
    ->not->toBeUsed();

arch('لا env() خارج ملفات config')
    ->expect('env')
    ->not->toBeUsedIn('Src')
    ->and('env')->not->toBeUsedIn('App');

arch('السياقات لا تستورد بعضها مباشرة')
    ->expect('Src\Contexts\Identity')
    ->not->toUse('Src\Contexts\Content\Domain')
    ->and('Src\Contexts\Content')
    ->not->toUse('Src\Contexts\Identity\Domain\Models');
```

> الاختبارات دي بتجري في ثواني وبتمسك ٨٠٪ من كسر القواعد المعمارية.

---

## ٤. اختبارات الصلاحيات

قالب جاهز — انسخه لكل مورد:

```php
// tests/Feature/Authorization/AnnouncementAuthorizationTest.php

dataset('roles_and_abilities', [
    ['super_admin', ['viewAny' => true,  'create' => true,  'update' => true,  'delete' => true,  'publish' => true]],
    ['admin',       ['viewAny' => true,  'create' => true,  'update' => true,  'delete' => true,  'publish' => true]],
    ['editor',      ['viewAny' => true,  'create' => true,  'update' => true,  'delete' => false, 'publish' => false]],
    ['viewer',      ['viewAny' => true,  'create' => false, 'update' => false, 'delete' => false, 'publish' => false]],
]);

it('يطبّق الصلاحيات الصحيحة لكل دور', function (string $role, array $expected) {
    $user = userWithRole($role);
    $record = Announcement::factory()->draft()->create();

    foreach ($expected as $ability => $allowed) {
        expect($user->can($ability, $ability === 'viewAny' || $ability === 'create' ? Announcement::class : $record))
            ->toBe($allowed, "الدور {$role} — الصلاحية {$ability}");
    }
})->with('roles_and_abilities');

it('يخفي الإجراءات الممنوعة في الجدول', function () {
    actingAsRole('viewer');
    $record = Announcement::factory()->create();

    livewire(ListAnnouncements::class)
        ->assertTableActionHidden('publish', $record)
        ->assertTableActionHidden('delete', $record);
});

it('يمنع الوصول المباشر بالـ URL', function () {
    actingAsRole('viewer');

    get(AnnouncementResource::getUrl('create'))->assertForbidden();
});
```

> ⚠️ الاختبار فوق بيستخدم `$user->can($ability, $model)` — **اسم قدرة مش اسم صلاحية**. ده الشكل الوحيد المسموح. راجع `docs/19-policies.md`.

### كل قاعدة أعمال في الـ Policy لها اختبار برسالتها

```php
it('يرفض بالسبب الصحيح', function () {
    $response = Gate::forUser(userWithRole('admin'))->inspect('publish', $published);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(__('authorization.denied.announcement.not_draft'));
});
```

### اختبار شامل: كل مورد له اختبار صلاحيات

```php
it('كل Resource له اختبار صلاحيات', function () {
    $resources = collect(Filament::getPanel('admin')->getResources());

    foreach ($resources as $resource) {
        // اسم الموديل مش اسم كلاس المورد — `UserResource` → `UserAuthorizationTest`
        $name = Str::beforeLast(class_basename($resource), 'Resource');
        $testFile = base_path("tests/Feature/Authorization/{$name}AuthorizationTest.php");

        expect(File::exists($testFile))->toBeTrue("ناقص اختبار صلاحيات: {$name}");
    }
});
```

---

## ٥. اختبارات عزل المستأجرين

```php
it('لا يسرّب بيانات بين المستأجرين', function (string $modelClass) {
    [$a, $b] = Tenant::factory()->count(2)->create();

    $modelClass::factory()->count(2)->create(['tenant_id' => $a->id]);
    $modelClass::factory()->count(5)->create(['tenant_id' => $b->id]);

    app(TenantContext::class)->set($a->id);
    expect($modelClass::count())->toBe(2);

    app(TenantContext::class)->set($b->id);
    expect($modelClass::count())->toBe(5);
})->with([
    Announcement::class,
    User::class,
    // ... كل موديل تابع لمستأجر
]);

it('كل موديل فيه tenant_id عليه الـ trait', function () {
    $violations = [];

    foreach (allDomainModels() as $model) {
        if (! Schema::hasColumn((new $model)->getTable(), 'tenant_id')) {
            continue;
        }

        if (! in_array(BelongsToTenant::class, class_uses_recursive($model), true)) {
            $violations[] = $model;
        }
    }

    expect($violations)->toBeEmpty('موديلات ناقصها BelongsToTenant: ' . implode(', ', $violations));
});
```

---

## ٦. اختبارات الترجمة

```php
it('تطابق مفاتيح الترجمة بين العربية والإنجليزية', function () {
    foreach (File::files(lang_path('ar')) as $file) {
        $arKeys = array_keys(Arr::dot(require $file->getPathname()));
        $enPath = lang_path('en/' . $file->getFilename());

        expect(File::exists($enPath))->toBeTrue("ناقص: en/{$file->getFilename()}");

        $enKeys  = array_keys(Arr::dot(require $enPath));
        $missing = array_diff($arKeys, $enKeys);
        $extra   = array_diff($enKeys, $arKeys);

        expect($missing)->toBeEmpty("ناقص في en: " . implode(', ', $missing));
        expect($extra)->toBeEmpty("زيادة في en: " . implode(', ', $extra));
    }
});

it('كل صلاحية لها تسمية مترجمة', function () {
    foreach (app(PermissionBuilder::class)->allPermissionNames() as $permission) {
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);
            expect(permission_label($permission))
                ->not->toContain('authorization.', "{$permission} غير مترجم في {$locale}");
        }
    }
});

it('لا توجد فئات CSS اتجاهية', function () {
    $violations = [];
    $pattern = '/\b(pl|pr|ml|mr|border-l|border-r|rounded-l|rounded-r|text-left|text-right)-/';

    foreach (File::allFiles(resource_path('views')) as $file) {
        if (preg_match($pattern, $file->getContents(), $m)) {
            $violations[] = "{$file->getRelativePathname()}: {$m[0]}";
        }
    }

    expect($violations)->toBeEmpty("فئات اتجاهية: \n" . implode("\n", $violations));
});

it('لا توجد ألوان مكتوبة مباشرة', function () {
    $violations = [];

    foreach (File::allFiles([src_path(), app_path(), resource_path('views')]) as $file) {
        if (str_contains($file->getPathname(), '/theme/')) {
            continue;   // ملف الثيم مسموح
        }

        if (preg_match('/#[0-9a-fA-F]{6}\b/', $file->getContents(), $m)) {
            $violations[] = "{$file->getRelativePathname()}: {$m[0]}";
        }
    }

    expect($violations)->toBeEmpty("ألوان مباشرة: \n" . implode("\n", $violations));
});
```

---

## ٧. اختبارات الأداء

```php
it('صفحة قائمة الإعلانات لا تتجاوز ١٠ استعلامات', function () {
    Announcement::factory()->count(100)->create();
    actingAsRole('admin');

    DB::enableQueryLog();
    livewire(ListAnnouncements::class)->assertOk();
    $count = count(DB::getQueryLog());

    expect($count)->toBeLessThanOrEqual(10, "عدد الاستعلامات: {$count}");
});

it('كل عمود قابل للبحث أو الترتيب عليه فهرس', function () {
    // يمر على كل Resource ويقارن أعمدة searchable/sortable بفهارس الجدول
    foreach (Filament::getPanel('admin')->getResources() as $resource) {
        // ... تفاصيل التنفيذ
    }
});
```

---

## ٨. اختبارات الواجهة (Livewire)

```php
it('ينشئ إعلاناً من الشاشة', function () {
    actingAsRole('admin');

    livewire(CreateAnnouncement::class)
        ->fillForm([
            'title' => ['ar' => 'إعلان تجريبي', 'en' => 'Test'],
            'body'  => ['ar' => 'النص', 'en' => 'Body'],
            'severity' => 'info',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas('announcements', ['severity' => 'info']);
});

it('يتحقق من الحقول الإلزامية', function () {
    actingAsRole('admin');

    livewire(CreateAnnouncement::class)
        ->fillForm([])
        ->call('create')
        ->assertHasFormErrors(['title.en' => 'required']);
});
```

---

## ٩. قائمة معايير القبول لكل تاسك

انسخ الجدول ده في وصف كل PR:

```markdown
### معايير القبول

- [ ] الصلاحية معرّفة في `config/authorization.php` ومترجمة ar + en
- [ ] `authorization:sync` اتنفّذ
- [ ] الموديل عليه `BelongsToTenant` (لو تابع لمستأجر)
- [ ] فهرس مركّب يبدأ بـ `tenant_id`
- [ ] صفر نص مكتوب — كله `__()`
- [ ] صفر `left`/`right` — كله `start`/`end`
- [ ] صفر لون مباشر — كله توكنز
- [ ] Policy موجودة، بترث `Policy`، وكل دوالها بترجّع `Response`
- [ ] الصلاحية **و** قواعد الأعمال في الـ Policy — مفيش قاعدة أعمال في الـ Resource
- [ ] كل الإجراءات عبر `->authorize()` والجماعية عبر `->authorizeIndividualRecords()`
- [ ] الحقول الحساسة عليها منع حفظ فعلي (`saved(false)`) + اختبار بيثبت
- [ ] كل قاعدة رفض لها رسالة مترجمة ar + en
- [ ] اختبار مصفوفة أدوار + اختبار لكل قاعدة أعمال + اختبار «المدير العام مابيتخطاش قواعد السلامة»
- [ ] اختبار عزل مستأجر
- [ ] اختبار المنطق (المسار السعيد + حالة فشل)
- [ ] استعلامات الصفحة ≤ ١٠ (Telescope)
- [ ] العمليات الحساسة مسجّلة في activitylog
- [ ] جولة للشاشة (≤ ٥ خطوات، ar + en) + حالة فاضية تعليمية بتاعتها (`docs/15` بند ١٠)
- [ ] اختبار قبول: `it()` لكل معيار قبول (بند ١٣)
- [ ] رحلة مستخدم في المتصفح: سايدبار ← جولة ← CRUD ← دور ممنوع، و`assertUiQuality` عربي/إنجليزي وموبايل وداكن (بند ١٣)
- [ ] `composer check` أخضر — الوضعين single و multi
- [ ] `composer test:browser` أخضر بعد `npm run build`
- [ ] الوثيقة اتحدّثت لو فيه pattern جديد
```

---

## ١٠. الأدوات

`composer.json`:

```json
"scripts": {
    "test":        "pest --parallel",
    "test:cov":    "pest --coverage --min=70",
    "test:arch":   "pest --group=arch",
    "lint":        ["pint --test", "phpstan analyse"],
    "fix":         "pint",
    "check":       ["@lint", "@test"]
}
```

`phpstan.neon`:

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 6
    paths: [app, src]
    ignoreErrors:
        - '#Unsafe usage of new static#'
```

> **الهدف:** level 6 في أسبوع ٤، level 8 في أسبوع ٦.

---

## ١١. CI

`.github/workflows/ci.yml` — كل PR لازم يعدّي:

1. `composer install`
2. `composer lint`
3. `composer test` — **مصفوفة** على `TENANCY_MODE: [single, multi]` (بند ١٢)
4. فحص أمني: `composer audit`
5. بناء الأصول: `npm ci && npm run build`

محلياً `composer check` بيشغّل الوضعين ورا بعض (`test` ثم `test:multi`).

مهمة `browser` منفصلة: `npm ci && npm run build` ← `npx playwright install --with-deps chromium`
← `composer test:browser`، ولقطات الفشل بتترفع كـ artifact (بند ١٣).

الـ PR ما يتدمجش إلا لو كل ده أخضر.

---

## ١٢. الاختبارات في وضعي تعدد المستأجرين

القالب بيشتغل في وضعين يتختاروا عند أول نشر (`docs/03`). **الاختبارات لازم
تعدّي في الاتنين** — وضع مابيتختبرش بيبوظ بصمت (`docs/21` بند ٢٦).

```bash
composer test          # single (الافتراضي من phpunit.xml)
composer test:multi    # multi — قاعدة حقيقية لكل مؤسسة
```

### الفرق في البنية

| | single | multi |
|---|---|---|
| عزل الاختبارات | `RefreshDatabase` (transaction) | `DatabaseTruncation` |
| ليه | أسرع | `CREATE DATABASE` مرفوض جوه transaction |
| قواعد المؤسسات | مفيش | `fc_test_tenant_*` — بتتعمل مع `Tenant::factory()` وبتتمسح بعد كل اختبار |
| جداول مستثناة من التفريغ | — | `settings` و`system_state` (صفوفها من الميجريشنز) |

- الاختيار في `tests/Pest.php` بـ `getenv('TENANCY_MODE')` — الملف بيتقرا قبل
  ما التطبيق يقوم فـ `config()` مش متاح.
- `dropTestTenantDatabases()` بيرفض يمسح أي قاعدة غير البادئة `fc_test_tenant_`
  من `phpunit.xml` — حماية لو الاختبار اتشغّل على الجهاز الغلط.

### قواعد كتابة الاختبار

- **استعلام خام على جدول مركزي = اتصال مركزي صريح:**
  `DB::connection(TenancyMode::centralConnection())->table('activity_log')`، و
  `assertDatabaseHas('onboarding_progress', [...], TenancyMode::centralConnection())`.
- **جدول مؤسسة جوه سياقها:** `app(TenantContext::class)->forTenant($id, fn () => ...)`.
- **سلوك مختلف بين الوضعين = اختبارين** بـ `->skip()` صريح بسبب مكتوب —
  مش `if` جوه اختبار واحد. مثال: تجاوز الـ scope بيجمع في single وبيرمي
  `CrossTenantQueryException` في multi (`tests/Feature/Tenancy/IsolationTest.php`).

### معايير القبول

- [ ] `composer test` أخضر في `TENANCY_MODE=single` و`TENANCY_MODE=multi`
- [ ] مفيش قواعد `fc_test_tenant_*` متبقية بعد التشغيل
- [x] الـ CI فيه مصفوفة على الوضعين

---

## ١٣. اختبارات القبول ورحلات المستخدم (إجباري لكل ميزة)

اختبار Livewire بيثبت إن الصفحة **بترندر** وإن الـ Policy **بتمنع**. مابيثبتش إن
المستخدم **يقدر يوصل ويخلّص شغله**: مابيشغّلش JavaScript، مابيضغطش على السايدبار،
ومابيشوفش الصفحة على موبايل. أول رحلة متصفح في القالب كشفت إن الجولات عمرها ما
اشتغلت (`docs/21` بند ٣٠ نقطة ١٣) — وكل اختبارات Livewire كانت خضرا.

### التلات ملفات لكل شاشة

| الملف | بيثبت إيه | المستوى |
|---|---|---|
| `tests/Feature/Authorization/{Model}AuthorizationTest.php` | مصفوفة الأدوار × القدرات (بند ٤) | Gate |
| `tests/Acceptance/{Name}AcceptanceTest.php` | معايير القبول بتاعة الميزة — `it()` لكل معيار، ونصه هو اسم الاختبار | HTTP / Livewire |
| `tests/Browser/{Panel}/{Name}JourneyTest.php` | رحلة المستخدم في متصفح حقيقي + جودة الواجهة | Playwright |

`FeatureTestCoverageTest` بيمشي على كل مورد وصفحة في **اللوحتين** وبيفشل لو ناقص
ملف قبول أو رحلة بيذكر الشاشة (بالكلاس). ملف واحد ممكن يغطي شاشات بنفس الشكل
(تبويبات الإعدادات مثلاً).

### الحد الأدنى للرحلة

1. تسجيل الدخول والوصول للشاشة **من السايدبار** مش بالرابط
2. الجولة بتبدأ لوحدها أول مرة، وبعد إغلاقها ماترجعش
3. الحالة الفاضية ← إنشاء ← السجل في القائمة ← تعديل ← حذف بتأكيد (للشاشات اللي فيها CRUD)
4. دور مالوش الصلاحية: العنصر مش في السايدبار، والرابط المباشر 403/404
5. `assertUiQuality()` في كل رحلة، وعلى `on()->mobile()` و`inDarkMode()` مرة على الأقل

### `assertUiQuality($page, $locale)`

- `assertNoJavaScriptErrors()` و`assertNoConsoleLogs()`
- `document.documentElement.dir` = `rtl` للعربي و`ltr` للإنجليزي — بـ `assertScript`،
  لأن `assertAttribute('html', …)` بيخمّن الـ selector كنص وبيستنى لحد المهلة
- مفيش مفتاح ترجمة خام (`ns::group.item`) ظاهر في الصفحة

### البنية

- `Tests\BrowserTestCase` = `TestCase` بـ `$usesBuiltAssets = true` — **من غير
  `withoutVite()`**: الجولات والثيم محتاجين الأصول الحقيقية، فلازم `npm run build` الأول.
- السيرفر بيشتغل **جوه نفس العملية** — `actingAs()` و`RefreshDatabase` بيشتغلوا عادي
  (وفي multi: `DatabaseTruncation` زي بند ١٢).
- `tests/Browser` **مش** في `phpunit.xml` عن قصد — ده اللي بيخلّي `composer test`
  سريع ومن غير متصفح. التشغيل: `composer test:browser`.
- لقطات الفشل في `tests/Browser/Screenshots` ومتجاهلة في git.

### معايير القبول

- [x] `FeatureTestCoverageTest` أخضر في اللوحتين — في الوضعين
- [x] `composer test:browser` أخضر، وفي multi كمان — ٧٦ حالة (٦٧ ناجحة + ٩ متخطّية لشاشات مفيهاش دور ممنوع) في الوضعين
- [x] كل رحلة فيها `assertUiQuality()` بلغتين، وموبايل ووضع داكن مرة على الأقل — `journeys.php`
- [x] مهمة `browser` في الـ CI بترفع لقطات الفشل (`ci.yml` — لسه مااشتغلتش على رانر نضيف)
