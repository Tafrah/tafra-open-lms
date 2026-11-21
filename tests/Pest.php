<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Server\Testing\TestResponse as McpTestResponse;
use Laravel\Mcp\Server\Tool as LaravelMcpTool;
use phpseclib4\Crypt\RSA;
use Spatie\Permission\PermissionRegistrar;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Authorization\CentralRoles;
use Src\Support\Presentation\Mcp\FcMcpServer;
use Src\Support\Presentation\Mcp\McpSession;
use Src\Support\Presentation\Mcp\McpToolFactory;
use Tests\BrowserTestCase;
use Tests\TestCase;

/*
 * وضع multi: تجهيز مؤسسة بيعمل `CREATE DATABASE` — وPostgres بيرفضه جوه
 * transaction، واللي `RefreshDatabase` بيفتحها حوالين كل اختبار. فبنفرّغ
 * الجداول بدل الـ transaction، وبنمسح قواعد المؤسسات بعد كل اختبار
 * (docs/17 بند ١٢). `getenv` مش `config()`: الملف ده بيتقرا قبل ما التطبيق يقوم.
 */
$multiTenancy = getenv('TENANCY_MODE') === 'multi';

uses(TestCase::class, $multiTenancy ? DatabaseTruncation::class : RefreshDatabase::class)
    ->afterEach(function () use ($multiTenancy): void {
        if ($multiTenancy) {
            dropTestTenantDatabases();
        }
    })
    ->in('Feature', 'Unit', 'Acceptance', '../src');
// مساعدات رحلات المتصفح (docs/17 بند ١٣)
require_once __DIR__.'/Support/journeys.php';

uses(TestCase::class)->in('Architecture');

// رحلات المستخدم: متصفح حقيقي بأصول مبنية — `composer test:browser` (docs/17 بند ١٣)
uses(BrowserTestCase::class, $multiTenancy ? DatabaseTruncation::class : RefreshDatabase::class)
    ->afterEach(function () use ($multiTenancy): void {
        if ($multiTenancy) {
            dropTestTenantDatabases();
        }
    })
    ->group('browser')
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| مساعدات مشتركة
|--------------------------------------------------------------------------
*/

/** المؤسسة الحالية للاختبار — بتتعمل مرة واحدة لكل اختبار. */
function currentTenant(): Tenant
{
    return test()->tenant ??= Tenant::factory()->create();
}

/**
 * بيضبط سياق المؤسسة **قبل** إسناد الدور — الأدوار مربوطة بالمؤسسة
 * (teams)، فالترتيب ده مش تفصيلة (docs/03 بند ٣).
 */
function userWithRole(string $role, ?Tenant $tenant = null): User
{
    $tenant ??= currentTenant();

    syncAuthorization();

    app(TenantContext::class)->set($tenant->getKey());

    $user = User::factory()->create();
    $user->tenants()->syncWithoutDetaching([$tenant->getKey() => ['joined_at' => now()]]);

    // دور فريق التشغيل (super_admin/support) على الفريق المركزي — مع
    // عضوية في المؤسسة عشان اختبارات لوحة المؤسسة تقدر تفتحها (docs/24 بند ٢)
    if (CentralRoles::isCentralRole($role)) {
        app(CentralRoles::class)->assign($user, $role);
    } else {
        $user->assignRole($role);
    }

    return $user->fresh();
}

/** سياق المؤسسة من غير مستخدم لوحة — لاختبارات الطالب والموقع العام. */
function inTenant(?Tenant $tenant = null): Tenant
{
    $tenant ??= currentTenant();

    app(TenantContext::class)->set($tenant->getKey());

    return $tenant;
}

/** مستخدم من فريق التشغيل بس — مش عضو في أي مؤسسة. */
function centralUser(string $role): User
{
    syncAuthorization();

    app(TenantContext::class)->set(null);

    $user = User::factory()->create();
    app(CentralRoles::class)->assign($user, $role);

    return $user->fresh();
}

/** بيسجّل الدخول على اللوحة المركزية — مفيش مؤسسة، والفريق = المركزي. */
function actingAsCentral(string $role): User
{
    $user = centralUser($role);

    test()->actingAs($user);

    Filament::setCurrentPanel('central');
    Filament::setTenant(null, isQuiet: true);
    app(TenantContext::class)->set(null);

    return $user;
}

/**
 * بيسجّل الدخول ويضبط لوحة Filament والمؤسسة — من غير الخطوتين دول
 * اختبارات صفحات Livewire بتفشل عند التركيب.
 */
function actingAsRole(string $role, ?Tenant $tenant = null): User
{
    $tenant ??= currentTenant();
    $user = userWithRole($role, $tenant);

    test()->actingAs($user);

    Filament::setCurrentPanel('admin');
    Filament::setTenant($tenant, isQuiet: true);
    app(TenantContext::class)->set($tenant->getKey());

    return $user;
}

/**
 * مستخدم بدور مؤقّت بصلاحيات محددة بالظبط.
 *
 * لازم لاختبار الحقول الحساسة: عايزين حد يقدر يعدّل السجل (عشان
 * الصفحة تفتح أصلاً) بس مايقدرش يعدّل الحقل الحساس.
 *
 * @param list<string> $permissions
 */
function userWithPermissions(array $permissions, ?Tenant $tenant = null, string $roleName = 'test_role'): User
{
    $tenant ??= currentTenant();

    syncAuthorization();

    $role = Role::findOrCreate($roleName, config('authorization.guard'));
    $role->syncPermissions($permissions);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(TenantContext::class)->set($tenant->getKey());

    $user = User::factory()->create();
    $user->tenants()->syncWithoutDetaching([$tenant->getKey() => ['joined_at' => now()]]);
    $user->assignRole($roleName);

    return $user->fresh();
}

/** بيسجّل الدخول بدور مؤقّت بصلاحيات محددة. */
function actingWithPermissions(array $permissions, ?Tenant $tenant = null, string $roleName = 'test_role'): User
{
    $tenant ??= currentTenant();
    $user = userWithPermissions($permissions, $tenant, $roleName);

    test()->actingAs($user);

    Filament::setCurrentPanel('admin');
    Filament::setTenant($tenant, isQuiet: true);
    app(TenantContext::class)->set($tenant->getKey());

    return $user;
}

/**
 * بيسجّل الدخول على اللوحة المركزية بدور مؤقّت بصلاحيات محددة على الفريق المركزي.
 *
 * @param list<string> $permissions
 */
function actingWithCentralPermissions(array $permissions, string $roleName = 'test_central_role'): User
{
    syncAuthorization();

    app(TenantContext::class)->set(null);

    $role = Role::findOrCreate($roleName, config('authorization.guard'));
    $role->syncPermissions($permissions);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();
    app(CentralRoles::class)->assign($user, $roleName);
    $user = $user->fresh();

    test()->actingAs($user);

    Filament::setCurrentPanel('central');
    Filament::setTenant(null, isQuiet: true);
    app(TenantContext::class)->set(null);

    return $user;
}

/**
 * بيمسح قواعد المؤسسات اللي اتعملت في الاختبار (وضع multi بس).
 *
 * الاسم محمي بالبادئة `fc_test_tenant_` من phpunit.xml — عمره ما يطول
 * قاعدة تطوير أو إنتاج حتى لو الاختبار اتشغّل على الجهاز الغلط.
 */
function dropTestTenantDatabases(): void
{
    $prefix = (string) config('tenancy.database.prefix');

    if (! str_starts_with($prefix, 'fc_test_tenant_')) {
        return;
    }

    tenancy()->end();

    $central = DB::connection((string) config('tenancy.database.central_connection'));

    $databases = $central->select(
        'SELECT datname FROM pg_database WHERE datname LIKE ?',
        [$prefix.'%'],
    );

    foreach ($databases as $database) {
        $central->statement(sprintf('DROP DATABASE IF EXISTS "%s" WITH (FORCE)', $database->datname));
    }
}

/**
 * فحوصات الجودة المشتركة لكل رحلة مستخدم (docs/17 بند ١٣):
 * صفر أخطاء JavaScript وصفر رسائل console، اتجاه الصفحة صح للّغة،
 * ومفيش مفتاح ترجمة خام ظاهر للمستخدم.
 *
 * @param object $page صفحة من `visit()`
 */
function assertUiQuality(object $page, string $locale = 'ar'): void
{
    // `assertAttribute('html', …)` بيخمّن الـ selector كنص ويستنى لحد المهلة —
    // الاتجاه بيتقرا من الـ DOM مباشرة
    // `ResizeObserver loop completed…` تحذير من Chromium نفسه وقت تغيّر المقاس (موبايل) — مش خطأ
    // في الصفحة، وبيطلع بشكل عشوائي. أي خطأ JavaScript تاني بيفشّل الرحلة.
    $errors = array_values(array_filter(
        (array) $page->script('(window.__pestBrowser && window.__pestBrowser.jsErrors || []).map(e => e.message)'),
        static fn (mixed $message): bool => ! str_starts_with((string) $message, 'ResizeObserver loop'),
    ));

    expect($errors)->toBeEmpty('أخطاء JavaScript في الصفحة: '.implode(' | ', array_map('strval', $errors)));

    $page->assertNoConsoleLogs()
        ->assertScript('document.documentElement.dir', in_array($locale, ['ar', 'he', 'fa', 'ur'], true) ? 'rtl' : 'ltr');

    expect(rawTranslationKeys((string) $page->script('document.body.innerText')))->toBeEmpty('مفاتيح ترجمة خام ظاهرة في الصفحة');
}

/**
 * مفاتيح ترجمة بتاعتنا ظاهرة كنص خام (`ns::group.item`).
 *
 * @return list<string>
 */
function rawTranslationKeys(string $text): array
{
    $namespaces = ['fc-onboarding'];

    foreach (File::directories(src_path('Contexts')) as $context) {
        if (File::isDirectory($context.'/Lang')) {
            $namespaces[] = strtolower(basename($context));
        }
    }

    // ملفات `lang/` الجذرية (`common.save`) مالهاش namespace — من غيرها مفتاح ناقص
    // في `lang/ar/common.php` بيظهر خام والرحلة خضرا
    $groups = array_map(
        static fn (SplFileInfo $file): string => $file->getBasename('.php'),
        File::files(lang_path('en')),
    );

    $quote = static fn (array $names): string => implode('|', array_map(
        static fn (string $name): string => preg_quote($name, '/'),
        $names,
    ));

    $pattern = '/(?<![\w\-.@\/])(?:(?:'.$quote($namespaces).')::[a-z0-9_]+|(?:'.$quote($groups).'))\.[a-z0-9_]+(?:\.[a-z0-9_]+)*/i';

    preg_match_all($pattern, $text, $found);

    return array_values(array_unique($found[0]));
}

/** بيزامن الصلاحيات مرة واحدة لكل اختبار. */
function syncAuthorization(): void
{
    static $synced = null;

    if ($synced === app()->make('db')->connection()->getName().spl_object_id(app())) {
        return;
    }

    Artisan::call('authorization:sync');

    $synced = app()->make('db')->connection()->getName().spl_object_id(app());
}

/** كل ملفات PHP تحت المسارات دي. */
function allPhpFiles(array $paths): array
{
    $files = [];

    foreach ($paths as $path) {
        if (! is_dir($path)) {
            continue;
        }

        foreach (File::allFiles($path) as $file) {
            // .blade.php مش كود PHP بالمعنى ده — الاختبارات المعمارية
            // بتتكلم عن الكلاسات مش عن العروض
            if ($file->getExtension() === 'php' && ! str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file;
            }
        }
    }

    return $files;
}

/** بيحوّل مسار ملف لاسم كلاس بالكامل. */
function classFromPath(string $path): ?string
{
    $relative = str_replace([base_path().DIRECTORY_SEPARATOR, '.php'], '', $path);
    $relative = str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

    foreach (['src\\' => 'Src\\', 'app\\' => 'App\\'] as $prefix => $namespace) {
        if (str_starts_with($relative, $prefix)) {
            return $namespace.substr($relative, strlen($prefix));
        }
    }

    return null;
}

/** @return list<class-string> كل موديلات الدومين في كل السياقات */
function allDomainModels(): array
{
    $models = [];

    foreach (allPhpFiles([src_path('Contexts')]) as $file) {
        if (! str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'Domain'.DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR)) {
            continue;
        }

        $class = classFromPath($file->getPathname());

        if ($class === null || ! class_exists($class)) {
            continue;
        }

        if (is_subclass_of($class, Model::class)) {
            $models[] = $class;
        }
    }

    return $models;
}

/** @return list<class-string> كل السياسات في كل السياقات */
function allPolicies(): array
{
    $policies = [];

    foreach (allPhpFiles([src_path('Contexts')]) as $file) {
        if (! str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'Policies'.DIRECTORY_SEPARATOR)) {
            continue;
        }

        $class = classFromPath($file->getPathname());

        if ($class !== null && class_exists($class)) {
            $policies[] = $class;
        }
    }

    return $policies;
}

/**
 * بيشيل التعليقات من كود PHP.
 *
 * الاختبارات المعمارية بتدوّر على أنماط زي `env(` و`$guarded = []` —
 * ولو ذكرنا النمط في تعليق بيشرح ليه ممنوع، الاختبار بيفشل على نفسه.
 * الشرح جزء من الكود الكويس، فالفحص هو اللي لازم يتعلّم يتجاهله.
 */
function phpCodeWithoutComments(string $contents): string
{
    $code = '';

    foreach (token_get_all($contents) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            // بنسيب أسطر جديدة مكانها عشان أرقام السطور ما تتغيّرش
            $code .= str_repeat("\n", substr_count($token[1], "\n"));

            continue;
        }

        $code .= is_array($token) ? $token[1] : $token;
    }

    return $code;
}

/** بيدوّر على نمط في كود كل ملفات PHP تحت مسارات معيّنة (بدون التعليقات). */
function grepAll(string $pattern, array $paths, array $ignorePathPatterns = [], bool $includeComments = false): array
{
    $matches = [];

    foreach (allPhpFiles($paths) as $file) {
        foreach ($ignorePathPatterns as $ignore) {
            if (preg_match($ignore, $file->getPathname())) {
                continue 2;
            }
        }

        $contents = $file->getContents();

        if (! $includeComments) {
            $contents = phpCodeWithoutComments($contents);
        }

        if (preg_match($pattern, $contents, $found)) {
            $matches[] = $file->getRelativePathname().': '.$found[0];
        }
    }

    return $matches;
}

expect()->extend('toBeForbiddenFor', function (string $role) {
    actingAsRole($role);
    test()->get($this->value)->assertForbidden();

    return $this;
});

/*
|--------------------------------------------------------------------------
| مساعدات خادم MCP (docs/23 بند ٧)
|--------------------------------------------------------------------------
| الاختبارات بتنادي الخادم من غير HTTP — فالجلسة بتتملى هنا بنفس اللي
| `AuthenticateMcpConnection` بيعمله: المؤسسة، اللوحة، حارس الويب، المستوى.
*/

/** مستخدم بدور، داخل MCP على المؤسسة الحالية بمستوى قراءة أو كتابة. */
function actingAsMcp(string $role, bool $write = true, ?Tenant $tenant = null): User
{
    $tenant ??= currentTenant();
    $user = userWithRole($role, $tenant);

    return startMcpSession($user, $write, $tenant);
}

/** بيفتح جلسة MCP لمستخدم موجود — `null` = اللوحة المركزية. */
function startMcpSession(User $user, bool $write = true, ?Tenant $tenant = null): User
{
    app(TenantContext::class)->set($tenant?->getKey());
    Filament::setCurrentPanel($tenant === null ? 'central' : 'admin');
    Filament::setTenant($tenant, isQuiet: true);
    auth()->guard('web')->setUser($user);

    app(McpSession::class)->start(
        user: $user,
        tenantId: $tenant?->getKey(),
        canWrite: $write,
        clientName: 'Test client',
    );

    return $user;
}

/** نسخة الأداة بالاسم من الإعلانات — أو أداة بالاسم ده بس لو مش متولّدة أصلاً. */
function mcpTool(string $name): LaravelMcpTool
{
    $panel = app(McpSession::class)->panelId();

    foreach (app(McpToolFactory::class)->toolsFor($panel) as $tool) {
        if ($tool->name() === $name) {
            return $tool;
        }
    }

    return new class($name) extends LaravelMcpTool
    {
        public function __construct(private readonly string $toolName) {}

        public function name(): string
        {
            return $this->toolName;
        }
    };
}

/** بينادي أداة على الخادم الحقيقي (tools/call) بالمستخدم الحالي. */
function callMcp(string $name, array $arguments = []): McpTestResponse
{
    return FcMcpServer::actingAs(app(McpSession::class)->user())->tool(mcpTool($name), $arguments);
}

/** أسماء الأدوات اللي الخادم بيعرضها للجلسة الحالية (tools/list). */
function mcpToolNames(): array
{
    $list = FcMcpServer::actingAs(app(McpSession::class)->user())->tools();

    return array_column((fn (): array => $this->items)->call($list), 'name');
}

/** الـ structuredContent من رد أداة. */
function mcpResult(McpTestResponse $response): array
{
    $payload = (fn () => $this->response)->call($response)->toArray();

    return (array) ($payload['result']['structuredContent'] ?? []);
}

/** نص رسالة الخطأ من رد أداة فشلت. */
function mcpError(McpTestResponse $response): string
{
    $payload = (fn () => $this->response)->call($response)->toArray();

    return (string) ($payload['result']['content'][0]['text'] ?? $payload['error']['message'] ?? '');
}

/**
 * مفاتيح Passport للاختبار — في الذاكرة ومرة واحدة للعملية، من غير ملفات.
 * في الإنتاج `php artisan passport:keys` (أو `PASSPORT_PRIVATE_KEY`) في الديبلوي.
 */
function usePassportTestKeys(): void
{
    static $keys = null;

    if ($keys === null) {
        // نفس اللي `passport:keys` بيعمله — phpseclib بيشتغل من غير openssl.cnf
        $key = RSA::createKey(2048);
        $keys = ['private' => (string) $key, 'public' => (string) $key->getPublicKey()];
    }

    config(['passport.private_key' => $keys['private'], 'passport.public_key' => $keys['public']]);
}
