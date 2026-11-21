<?php

declare(strict_types=1);

use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchema as JsonSchemaFactory;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Domain\Authorization\Policy;
use Src\Support\Presentation\Mcp\McpRegistry;
use Src\Support\Presentation\Mcp\McpResource;
use Src\Support\Presentation\Mcp\McpToolFactory;
use Src\Support\Presentation\Mcp\SystemMap\SystemMap;
use Src\Support\Presentation\Mcp\Tools\FcTool;

/*
|--------------------------------------------------------------------------
| عقد MCP لكل ميزة (docs/23 بند ٧ · قاعدة ١٢ في CLAUDE.md)
|--------------------------------------------------------------------------
| مورد جديد من غير إعلان MCP، أو قدرة Policy نسيناها، أو شاشة مالهاش وصف
| للذكاء الاصطناعي = الاختبار ده أحمر. «تحكم كامل» قرار مكتوب، مش نسيان.
*/

/** @return list<McpResource> */
function mcpDeclarations(string $panelId): array
{
    return array_map(static fn (string $class): McpResource => app($class), app(McpRegistry::class)->resourcesFor($panelId));
}

/**
 * القدرات العامة اللي الـ Policy بتعرّفها بنفسها (أول معامل User).
 *
 * @return list<string>
 */
function policyAbilities(Policy $policy): array
{
    $abilities = [];

    foreach ((new ReflectionClass($policy))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        $first = $method->getParameters()[0] ?? null;

        if ($method->isStatic()
            || $method->getDeclaringClass()->getName() !== $policy::class
            || $first?->getType()?->getName() !== User::class) {
            continue;
        }

        $abilities[] = $method->getName();
    }

    return $abilities;
}

/** المفتاح بيترجم في كل اللغات المدعومة (مش بيرجع نفسه). */
function translatedEverywhere(string $key): bool
{
    foreach ((array) config('app.supported_locales') as $locale) {
        $value = __($key, [], (string) $locale);

        if (! is_string($value) || $value === '' || $value === $key) {
            return false;
        }
    }

    return true;
}

it('كل مورد Filament ليه إعلان MCP أو إعفاء بسبب مترجم', function (string $panelId): void {
    $exempt = (array) config('ai.exempt', []);
    $missing = [];

    foreach (Filament::getPanel($panelId)->getResources() as $resource) {
        if (app(McpRegistry::class)->declarationFor($resource, $panelId) !== null) {
            continue;
        }

        if (isset($exempt[$resource]) && translatedEverywhere((string) $exempt[$resource])) {
            continue;
        }

        $missing[] = $resource;
    }

    expect($missing)->toBeEmpty(
        "موارد من غير إعلان MCP في {$panelId} — اعمل `Presentation/Mcp/<Model>Mcp.php` (أو `Mcp/Central/`) أو إعفاء في `ai.exempt` بسبب:\n".implode("\n", $missing),
    );
})->with(['admin', 'central']);

it('كل قدرة في الـ Policy يا متاحة يا مستبعدة بسبب مترجم', function (string $panelId): void {
    $problems = [];

    foreach (mcpDeclarations($panelId) as $declaration) {
        $name = class_basename($declaration);
        $policy = $declaration->policy();

        if ($policy === null) {
            $problems[] = "{$name}: الموديل مالوش Policy";

            continue;
        }

        $exposed = $declaration->exposedAbilities();
        $omitted = $declaration->omitted();

        foreach (policyAbilities($policy) as $ability) {
            if (! in_array($ability, $exposed, true) && ! array_key_exists($ability, $omitted)) {
                $problems[] = "{$name}: القدرة `{$ability}` لا متاحة ولا في omitted()";
            }
        }

        foreach ($omitted as $ability => $reason) {
            if (in_array($ability, $exposed, true)) {
                $problems[] = "{$name}: `{$ability}` متاحة ومستبعدة في نفس الوقت";
            }

            if (! translatedEverywhere($reason)) {
                $problems[] = "{$name}: سبب استبعاد `{$ability}` ({$reason}) مش مترجم بكل اللغات";
            }
        }
    }

    expect($problems)->toBeEmpty(implode("\n", $problems));
})->with(['admin', 'central']);

it('الممنوع مايتعلنش أصلاً — والموارد للقراءة بس مالهاش كتابة', function (string $panelId): void {
    $forbidden = (array) config('ai.forbidden_abilities');
    $readOnly = (array) config('ai.read_only_resources');
    $problems = [];

    foreach (mcpDeclarations($panelId) as $declaration) {
        foreach ($declaration->exposedAbilities() as $ability) {
            if (in_array($ability, $forbidden, true)) {
                $problems[] = class_basename($declaration).": `{$ability}` ممنوعة (ai.forbidden_abilities)";
            }

            if (in_array($declaration->key(), $readOnly, true) && ! in_array($ability, ['viewAny', 'view'], true)) {
                $problems[] = class_basename($declaration).": `{$ability}` كتابة على مورد للقراءة بس";
            }
        }
    }

    expect($problems)->toBeEmpty(implode("\n", $problems));
})->with(['admin', 'central']);

it('كل أداة: اسم صالح وفريد، وصف مترجم، annotations صريحة، ومفيش tenant_id', function (string $panelId): void {
    $tools = app(McpToolFactory::class)->toolsFor($panelId);
    $names = [];
    $problems = [];

    foreach ($tools as $tool) {
        $name = $tool->name();

        if (! preg_match('/^[a-z0-9_]{1,64}$/', $name)) {
            $problems[] = "{$name}: اسم مش صالح لعملاء MCP";
        }

        if (isset($names[$name])) {
            $problems[] = "{$name}: الاسم متكرر";
        }

        $names[$name] = true;

        if (! $tool instanceof FcTool) {
            $problems[] = "{$name}: لازم ترث FcTool (تسجيل، رفض مترجم، حدود)";

            continue;
        }

        $annotations = $tool->annotations();

        foreach (['readOnlyHint', 'destructiveHint', 'idempotentHint', 'openWorldHint'] as $hint) {
            if (! array_key_exists($hint, $annotations)) {
                $problems[] = "{$name}: ناقصها {$hint}";
            }
        }

        if ($tool->writes() === ($annotations['readOnlyHint'] ?? true)) {
            $problems[] = "{$name}: readOnlyHint عكس writes()";
        }

        foreach ((array) config('app.supported_locales') as $locale) {
            app()->setLocale((string) $locale);

            if (str_contains($tool->description(), 'mcp.') || str_contains($tool->title(), 'mcp.')) {
                $problems[] = "{$name}: وصف أو عنوان مش مترجم ({$locale})";
            }
        }

        $schema = json_encode(JsonSchemaFactory::object(fn (JsonSchema $s): array => $tool->schema($s))->toArray());

        if (str_contains((string) $schema, 'tenant_id')) {
            $problems[] = "{$name}: فيه tenant_id في الـ schema — المؤسسة من الربط بس";
        }

        if (preg_match('/"ids"|bulk/i', (string) $schema.$name)) {
            $problems[] = "{$name}: شكلها أداة جماعية — سجل واحد بس";
        }
    }

    app()->setLocale((string) config('app.locale'));

    expect($problems)->toBeEmpty(implode("\n", $problems));
})->with(['admin', 'central']);

it('كل شاشة في اللوحتين ليها وصف للذكاء الاصطناعي بكل اللغات', function (string $panelId): void {
    $panel = Filament::getPanel($panelId);
    $map = app(SystemMap::class);
    $missing = [];

    $screens = [
        ...array_map(static fn (string $resource): array => [$resource, true], $panel->getResources()),
        ...array_map(static fn (string $page): array => [$page, false], array_filter(
            [...$panel->getPages(), $panel->getTenantProfilePage(), $panel->getProfilePage()],
            static fn (mixed $page): bool => is_string($page) && is_subclass_of($page, Page::class) && ! is_subclass_of($page, Cluster::class),
        )),
    ];

    foreach ((array) config('app.supported_locales') as $locale) {
        app()->setLocale((string) $locale);

        foreach ($screens as [$class, $isResource]) {
            $description = $isResource ? $map->describeResource($class, $panelId) : $map->describe($class, $panelId);

            if (trim($description) === '') {
                $missing[] = "{$locale}: ".class_basename($class);
            }
        }
    }

    app()->setLocale((string) config('app.locale'));

    expect($missing)->toBeEmpty(
        "شاشات من غير وصف — الوصف من الجولة، أو `DescribesForAi`، أو سبب الإعفاء:\n".implode("\n", $missing),
    );
})->with(['admin', 'central']);

it('تسميات حقول الإعلانات بتترجم بكل اللغات', function (string $panelId): void {
    $problems = [];

    foreach (mcpDeclarations($panelId) as $declaration) {
        foreach ($declaration->fields() as $field) {
            $reflection = new ReflectionProperty($field, 'labelKey');
            $key = $reflection->getValue($field);

            if (! is_string($key) || ! translatedEverywhere($key)) {
                $problems[] = class_basename($declaration).'.'.$field->name().': '.var_export($key, true);
            }
        }
    }

    expect($problems)->toBeEmpty("حقول من غير تسمية مترجمة:\n".implode("\n", $problems));
})->with(['admin', 'central']);

it('مفيش استعلامات ولا DB جوه طبقة MCP — الأداة بتنادي Action', function (): void {
    $directories = [src_path('Support/Presentation/Mcp'), ...glob(src_path('Contexts/*/Presentation/Mcp'), GLOB_ONLYDIR) ?: []];
    $violations = [];

    foreach (allPhpFiles($directories) as $file) {
        $code = phpCodeWithoutComments($file->getContents());

        // `query()` في McpResource نفسه = استعلام Filament الجاهز، مش استعلام جديد
        if (str_ends_with($file->getPathname(), 'McpResource.php')) {
            continue;
        }

        if (preg_match('/(::query\(|DB::|(?<!\$this)->(?:where|update|delete|save|create|forceDelete)\()/', $code, $found)) {
            $violations[] = "{$file->getRelativePathname()}: {$found[0]}";
        }
    }

    expect($violations)->toBeEmpty("منطق أو استعلام في طبقة MCP:\n".implode("\n", $violations));
});

it('Mcp::local مش متسجّل — الوصول المحلي بمصادقة برضه', function (): void {
    expect(grepAll('/Mcp::local\(/', [src_path(), app_path(), base_path('routes')]))->toBeEmpty();
});

it('كل Policy لموديل معلن عنه موجودة', function (): void {
    foreach (['admin', 'central'] as $panelId) {
        foreach (mcpDeclarations($panelId) as $declaration) {
            expect(Gate::getPolicyFor($declaration->model()))->toBeInstanceOf(Policy::class);
        }
    }
});
