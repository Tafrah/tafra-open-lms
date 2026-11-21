<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Providers;

use FutureCode\Onboarding\Contracts\OnboardingStep;
use FutureCode\Onboarding\OnboardingRegistry;
use FutureCode\Onboarding\Tours\Tour;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Server\Tool;
use Src\Support\Infrastructure\Tenancy\TenancyMode;
use Src\Support\Presentation\Filament\ContextPanelRegistry;
use Src\Support\Presentation\Mcp\McpRegistry;
use Src\Support\Presentation\Mcp\McpResource;

/**
 * الأساس المشترك لمزوّدي السياقات.
 *
 * كل سياق بيعرّف اسمه ومساره بس — التسجيل (ميجريشنز، ترجمة، مسارات،
 * اكتشاف موارد Filament) بيحصل هنا مرة واحدة.
 */
abstract class ContextServiceProvider extends ServiceProvider
{
    /** اسم السياق — بيتستخدم كـ namespace للترجمة: __('identity::identity.x') */
    abstract protected function name(): string;

    /** المسار المطلق لمجلد السياق */
    abstract protected function path(): string;

    /**
     * اللوحات اللي السياق بيسجّل عليها شاشاته — كل لوحة بتقرا من مجلدها
     * (`Presentation/Filament` أو `Presentation/Filament/Central`).
     *
     * @return list<string>
     */
    protected function panelIds(): array
    {
        return ['admin', 'central'];
    }

    public function register(): void
    {
        // التسجيل في register() مقصود: لازم يتم قبل ما اللوحة تتبني في الـ boot.
        foreach ($this->panelIds() as $panelId) {
            $this->app->make(ContextPanelRegistry::class)->registerContext(
                $panelId,
                $this->contextNamespace(),
                $this->path(),
            );
        }

        /*
         * ⚠️ الترجمة بتتسجّل في register() مش boot() — وده مش تفضيل أسلوبي.
         *
         * مزوّدو الباكدجات بيعملوا boot **قبل** مزوّدينا. FilamentServiceProvider
         * بيبني اللوحة في boot بتاعه، واللوحة فيها تسميات بتستدعي
         * `__('identity::...')` وقت البناء. لو الـ namespace ماكانش متسجّل
         * لسه، الـ Translator بيخزّن النتيجة **الفاضية** للطلب كله —
         * فاللوحة بتعرض مفاتيح خام بدل النصوص، وكل الاختبارات بتفضل خضرا
         * لأن الصفحة بترجّع 200 عادي.
         *
         * `register()` بتشتغل قبل أي `boot()`، فالـ hint بيبقى موجود مهما
         * كان ترتيب المزوّدين. راجع docs/21 بند ١٨.
         */
        if (is_dir($lang = $this->path().'/Lang')) {
            $this->loadTranslationsFrom($lang, $this->name());
        }

        // ميجريشنز جداول المستأجرين — `tenants:migrate` بيقرا المسارات دي
        // في وضع multi (docs/03 بند ٣)
        // `config:cache` بيشغّل register() وبيحفظ المسارات — من غير الفحص ده بتتكرر وقت التشغيل
        if (is_dir($tenantMigrations = $this->tenantMigrationsPath())
            && ! in_array($tenantMigrations, (array) $this->app['config']->get('tenancy.migration_parameters.--path', []), true)) {
            $this->app['config']->push('tenancy.migration_parameters.--path', $tenantMigrations);
        }

        $this->registerBindings();
    }

    public function boot(): void
    {
        if (is_dir($migrations = $this->path().'/Database/Migrations')) {
            $this->loadMigrationsFrom($migrations);
        }

        // في single، جداول المستأجرين في نفس القاعدة → `migrate` العادي.
        // في multi، مكانها قواعد المؤسسات → `tenants:migrate` بس.
        if (TenancyMode::sharesCentralDatabase() && is_dir($tenantMigrations = $this->tenantMigrationsPath())) {
            $this->loadMigrationsFrom($tenantMigrations);
        }

        if (is_file($routes = $this->path().'/Routes/web.php')) {
            $this->loadRoutesFrom($routes);
        }

        if (is_dir($views = $this->path().'/Presentation/Views')) {
            $this->loadViewsFrom($views, $this->name());
        }

        $this->discoverOnboarding();

        $this->discoverMcp();

        $this->bootContext();
    }

    /**
     * الخطوات والجولات بتتسجّل بالاكتشاف — ميزة جديدة بجولتها بتشتغل من غير
     * ما حد يفتكر يضيفها لقايمة (`docs/15` بند ١٠). `OnboardingCoverageTest`
     * بيتأكد إن كل شاشة ليها جولة.
     */
    protected function discoverOnboarding(): void
    {
        $registry = $this->app->make(OnboardingRegistry::class);

        foreach ($this->classesIn('Application/Onboarding') as $class) {
            if (is_subclass_of($class, OnboardingStep::class)) {
                $registry->step($class);
            }
        }

        foreach ($this->classesIn('Presentation/Tours') as $class) {
            if (is_subclass_of($class, Tour::class)) {
                $registry->tour($class);
            }
        }
    }

    /**
     * إعلانات MCP والأدوات بتتسجّل بالاكتشاف — مورد جديد بإعلانه بيظهر
     * للذكاء الاصطناعي من غير قايمة (docs/23 بند ٢). `McpCoverageTest`
     * بيتأكد إن كل مورد Filament ليه إعلان أو إعفاء بسبب.
     *
     * `Presentation/Mcp/Central/*` للوحة المركزية، والباقي للوحة المؤسسة —
     * نفس تقسيم `Presentation/Filament`.
     */
    protected function discoverMcp(): void
    {
        $registry = $this->app->make(McpRegistry::class);

        foreach ($this->classesIn('Presentation/Mcp') as $class) {
            $panelId = str_contains($class, '\\Presentation\\Mcp\\Central\\') ? 'central' : 'admin';

            if (is_subclass_of($class, McpResource::class) && ! (new \ReflectionClass($class))->isAbstract()) {
                $registry->resource($panelId, $class);
            } elseif (is_subclass_of($class, Tool::class) && ! (new \ReflectionClass($class))->isAbstract()) {
                $registry->tool($panelId, $class);
            }
        }
    }

    /** @return list<class-string> الكلاسات الموجودة فعلاً تحت مسار جوه السياق */
    private function classesIn(string $relative): array
    {
        $directory = $this->path().'/'.$relative;

        if (! is_dir($directory)) {
            return [];
        }

        $classes = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if (! $file instanceof \SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $relativeClass = str_replace(
                ['/', '.php'],
                ['\\', ''],
                substr($file->getPathname(), strlen($this->path()) + 1),
            );

            $class = $this->contextNamespace().'\\'.$relativeClass;

            if (class_exists($class)) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }

    /** ربط الواجهات بتنفيذاتها */
    protected function registerBindings(): void {}

    /** أي إقلاع خاص بالسياق: مستمعين، Observers، Gates */
    protected function bootContext(): void {}

    protected function tenantMigrationsPath(): string
    {
        return $this->path().'/Database/Migrations/Tenant';
    }

    protected function contextNamespace(): string
    {
        return substr(static::class, 0, (int) strrpos(static::class, '\\'));
    }
}
