<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * مولّد الميزات — بيولّد الهيكل الكامل بالبنية الصحيحة
 * (docs/16 بند «مولّد آلي»).
 *
 * `php artisan make:fc-feature Content Announcement --translatable --soft-deletes`
 */
final class MakeFeatureCommand extends Command
{
    protected $signature = 'make:fc-feature
                            {context : اسم السياق (Content)}
                            {model : اسم الموديل بالمفرد (Announcement)}
                            {--translatable : حقول مترجمة (title, body)}
                            {--soft-deletes : حذف ناعم}
                            {--force : الكتابة فوق الملفات الموجودة}';

    protected $description = 'توليد ميزة كاملة بالبنية المعمارية الصحيحة';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $context = Str::studly((string) $this->argument('context'));
        $model = Str::studly((string) $this->argument('model'));
        $plural = Str::snake(Str::pluralStudly($model));
        $base = base_path("src/Contexts/{$context}");

        if (! $this->files->isDirectory($base)) {
            $this->error("السياق [{$context}] مش موجود. اعمله الأول أو اختار سياق موجود.");

            return self::FAILURE;
        }

        $replacements = [
            '{{ context }}' => $context,
            '{{ model }}' => $model,
            '{{ modelVariable }}' => Str::camel($model),
            '{{ modelPlural }}' => Str::pluralStudly($model),
            '{{ table }}' => $plural,
            '{{ resourceKey }}' => $plural,
            '{{ langKey }}' => Str::snake($model),
            '{{ contextLang }}' => Str::snake($context),
            '{{ softDeletesColumn }}' => $this->option('soft-deletes') ? "\n            \$table->softDeletes();" : '',
            '{{ softDeletes }}' => $this->option('soft-deletes') ? "\n    use SoftDeletes;" : '',
            '{{ softDeletesImport }}' => $this->option('soft-deletes')
                ? "\nuse Illuminate\\Database\\Eloquent\\SoftDeletes;"
                : '',
            // الأعمدة `json` — من غير ترجمة لازم cast، وإلا Eloquent بيبعت المصفوفة خام وبيفشل
            '{{ jsonCasts }}' => $this->option('translatable')
                ? ''
                : "\n            'title' => 'array',\n            'body' => 'array',",
            '{{ translatable }}' => $this->option('translatable')
                ? "\n    /** @var list<string> */\n    public array \$translatable = ['title', 'body'];\n"
                : '',
        ];

        $created = [];

        foreach ($this->blueprint($context, $model) as $relativePath => $stub) {
            $path = "{$base}/{$relativePath}";

            if ($this->files->exists($path) && ! $this->option('force')) {
                $this->warn("موجود، اتخطّى: {$relativePath}");

                continue;
            }

            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->put($path, rtrim(strtr($stub, $replacements)).PHP_EOL);

            $created[] = $relativePath;
        }

        foreach ($created as $file) {
            $this->line("  <info>+</info> {$file}");
        }

        $this->formatGenerated($base);

        $this->newLine();
        $this->info('اتولّدت الميزة. الخطوات الباقية بالترتيب:');
        $this->line("  ١. ضيف المورد '{$plural}' في config/authorization.php + الترجمة");
        $this->line('  ٢. php artisan authorization:sync');
        $this->line('  ٣. راجع الميجريشن وضيف الفهارس المطلوبة');
        $this->line('  ٤. المورد في Presentation/Filament/Resources (أو Filament/Central/ للوحة المركزية)');
        $this->line('     بحالة فاضية تعليمية + جولة في Presentation/Tours — OnboardingCoverageTest بيفشل من غيرهم');
        $this->line("  ٥. إعلان MCP: Presentation/Mcp/{$model}Mcp.php (انسخ AnnouncementMcp) — الحقول والعمليات،");
        $this->line('     وكل قدرة في الـ Policy يا متاحة يا في omitted() بسبب مترجم — McpCoverageTest بيفشل من غيره');
        $this->line("  ٦. الاختبارات: tests/Feature/Authorization/{$model}AuthorizationTest.php + كمّل");
        $this->line("     Tests/Acceptance/{$model}AcceptanceTest.php + رحلة tests/Browser/Admin/{$model}JourneyTest.php");
        $this->line('     (انسخ AnnouncementJourneyTest) — FeatureTestCoverageTest بيفشل من غيرهم');
        $this->line("  ٧. لو ده سياق جديد: ضيف `src/Contexts/{$context}/Database/Migrations/Tenant`");
        $this->line('     في `databaseMigrationsPath` بملف phpstan.neon، وسجّل مزوّد السياق');
        $this->line('     في bootstrap/providers.php');

        return self::SUCCESS;
    }

    /**
     * الكود المولّد لازم يعدّي `composer lint` من غير أي تدخّل —
     * فبنشغّل Pint عليه بدل ما نحاول نطابق كل قاعدة تنسيق في الـ stubs
     * (وبكده الـ stubs مابتقدمش لما القواعد تتغيّر).
     */
    private function formatGenerated(string $path): void
    {
        $pint = base_path('vendor/bin/pint');

        if (! $this->files->exists($pint)) {
            return;
        }

        $process = Process::fromShellCommandline(
            escapeshellarg(PHP_BINARY).' '.escapeshellarg($pint).' '.escapeshellarg($path).' --quiet',
            base_path(),
        );

        $process->run();
    }

    /** @return array<string, string> */
    private function blueprint(string $context, string $model): array
    {
        $date = now()->format('Y_m_d_His');
        $table = Str::snake(Str::pluralStudly($model));

        return [
            "Domain/Models/{$model}.php" => $this->modelStub(),
            "Domain/Enums/{$model}Status.php" => $this->enumStub(),
            "Domain/Events/{$model}Created.php" => $this->eventStub(),
            "Application/Actions/Create{$model}Action.php" => $this->actionStub(),
            "Application/DataObjects/{$model}Data.php" => $this->dtoStub(),
            "Infrastructure/Policies/{$model}Policy.php" => $this->policyStub(),
            // جدول تابع لمؤسسة = `Migrations/Tenant`: `migrate` في single و`tenants:migrate`
            // في multi. في المسار المركزي كان هيتعمل في القاعدة المركزية بس (docs/03)
            "Database/Migrations/Tenant/{$date}_create_{$table}_table.php" => $this->migrationStub(),
            "Database/Factories/{$model}Factory.php" => $this->factoryStub(),
            // معايير القبول جنب السياق — FeatureTestCoverageTest بيدوّر هنا كمان (docs/17 بند ١٣)
            "Tests/Acceptance/{$model}AcceptanceTest.php" => $this->testStub(),
        ];
    }

    private function modelStub(): string
    {
        return <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Src\Contexts\{{ context }}\Domain\Models;

            use Illuminate\Database\Eloquent\Factories\HasFactory;
            use Illuminate\Database\Eloquent\Model;{{ softDeletesImport }}
            use Spatie\Activitylog\Models\Concerns\LogsActivity;
            use Spatie\Activitylog\Support\LogOptions;
            use Spatie\Translatable\HasTranslations;
            use Src\Contexts\{{ context }}\Database\Factories\{{ model }}Factory;
            use Src\Contexts\{{ context }}\Domain\Enums\{{ model }}Status;
            use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

            final class {{ model }} extends Model
            {
                use BelongsToTenant;

                /** @use HasFactory<{{ model }}Factory> */
                use HasFactory;

                use HasTranslations;
                use LogsActivity;{{ softDeletes }}
            {{ translatable }}
                /** @var list<string> */
                protected $fillable = ['title', 'body', 'status'];

                public function getActivitylogOptions(): LogOptions
                {
                    return LogOptions::defaults()
                        ->logOnly(['title', 'status'])
                        ->logOnlyDirty()
                        ->dontLogEmptyChanges()
                        ->useLogName('{{ contextLang }}');
                }

                protected static function newFactory(): {{ model }}Factory
                {
                    return {{ model }}Factory::new();
                }

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return [
                        'status' => {{ model }}Status::class,{{ jsonCasts }}
                    ];
                }
            }
            PHP;
    }

    private function enumStub(): string
    {
        return <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Src\Contexts\{{ context }}\Domain\Enums;

            use Filament\Support\Contracts\HasColor;
            use Filament\Support\Contracts\HasLabel;

            enum {{ model }}Status: string implements HasColor, HasLabel
            {
                case Draft = 'draft';
                case Active = 'active';
                case Archived = 'archived';

                public function getLabel(): string
                {
                    return __("{{ contextLang }}::{{ contextLang }}.{{ langKey }}.status.{$this->value}");
                }

                public function getColor(): string
                {
                    return match ($this) {
                        self::Draft => 'gray',
                        self::Active => 'success',
                        self::Archived => 'warning',
                    };
                }
            }
            PHP;
    }

    private function eventStub(): string
    {
        return <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Src\Contexts\{{ context }}\Domain\Events;

            use Src\Support\Domain\Events\DomainEvent;

            final readonly class {{ model }}Created extends DomainEvent
            {
                public function __construct(
                    public int ${{ modelVariable }}Id,
                    int $tenantId,
                ) {
                    parent::__construct($tenantId);
                }
            }
            PHP;
    }

    private function actionStub(): string
    {
        return <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Src\Contexts\{{ context }}\Application\Actions;

            use Illuminate\Contracts\Events\Dispatcher;
            use Illuminate\Support\Facades\DB;
            use Illuminate\Support\Facades\Gate;
            use Src\Contexts\{{ context }}\Application\DataObjects\{{ model }}Data;
            use Src\Contexts\{{ context }}\Domain\Events\{{ model }}Created;
            use Src\Contexts\{{ context }}\Domain\Models\{{ model }};
            use Src\Contexts\Identity\Domain\Models\User;

            final readonly class Create{{ model }}Action
            {
                public function __construct(
                    private Dispatcher $events,
                ) {}

                public function handle({{ model }}Data $data, ?User $actor = null): {{ model }}
                {
                    // الحاجز الأخير — الـ Action ممكن يتنادى من API أو Job أو Command
                    if ($actor !== null) {
                        Gate::forUser($actor)->authorize('create', {{ model }}::class);
                    }

                    return DB::transaction(function () use ($data): {{ model }} {
                        ${{ modelVariable }} = {{ model }}::create($data->toArray());

                        $this->events->dispatch(new {{ model }}Created(
                            (int) ${{ modelVariable }}->getKey(),
                            (int) ${{ modelVariable }}->tenant_id,
                        ));

                        return ${{ modelVariable }};
                    });
                }
            }
            PHP;
    }

    private function dtoStub(): string
    {
        return <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Src\Contexts\{{ context }}\Application\DataObjects;

            final readonly class {{ model }}Data
            {
                /**
                 * @param  array<string, string>  $title
                 * @param  array<string, string>  $body
                 */
                public function __construct(
                    public array $title,
                    public array $body,
                    public string $status = 'draft',
                ) {}

                /** @param  array<string, mixed>  $data */
                public static function fromArray(array $data): self
                {
                    return new self(
                        title: (array) ($data['title'] ?? []),
                        body: (array) ($data['body'] ?? []),
                        status: (string) ($data['status'] ?? 'draft'),
                    );
                }

                /** @return array<string, mixed> */
                public function toArray(): array
                {
                    return ['title' => $this->title, 'body' => $this->body, 'status' => $this->status];
                }
            }
            PHP;
    }

    private function policyStub(): string
    {
        return <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Src\Contexts\{{ context }}\Infrastructure\Policies;

            use Illuminate\Auth\Access\Response;
            use Src\Contexts\{{ context }}\Domain\Models\{{ model }};
            use Src\Contexts\Identity\Domain\Models\User;
            use Src\Support\Application\Contracts\TenantContext;
            use Src\Support\Domain\Authorization\Policy;

            final class {{ model }}Policy extends Policy
            {
                protected function resource(): string
                {
                    return '{{ resourceKey }}';
                }

                /** قدرات لا يتجاوزها المدير العام — قواعد سلامة مش صلاحيات */
                public function invariants(): array
                {
                    return ['delete'];
                }

                public function viewAny(User $user): Response
                {
                    return $this->decide()->permission($user, $this, 'view_any')->response();
                }

                public function view(User $user, {{ model }} $record): Response
                {
                    return $this->decide()
                        ->permission($user, $this, 'view')
                        ->ruleOrNotFound(
                            $record->tenant_id === app(TenantContext::class)->id(),
                            'record_not_found',
                        )
                        ->response();
                }

                public function create(User $user): Response
                {
                    return $this->decide()->permission($user, $this, 'create')->response();
                }

                public function update(User $user, {{ model }} $record): Response
                {
                    return $this->decide()
                        ->permission($user, $this, 'update')
                        ->response();
                }

                public function delete(User $user, {{ model }} $record): Response
                {
                    return $this->decide()
                        ->permission($user, $this, 'delete')
                        ->response();
                }
            }
            PHP;
    }

    private function migrationStub(): string
    {
        return <<<'PHP'
            <?php

            declare(strict_types=1);

            use Illuminate\Database\Migrations\Migration;
            use Illuminate\Database\Schema\Blueprint;
            use Illuminate\Support\Facades\Schema;
            use Src\Support\Infrastructure\Persistence\TenantBlueprint;

            return new class extends Migration
            {
                public function up(): void
                {
                    Schema::create('{{ table }}', function (Blueprint $table): void {
                        $table->id();
                        // مفيش `constrained()`: في multi جدول `tenants` في القاعدة المركزية
                        $table->foreignId('tenant_id');
                        TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
                        $table->json('title');
                        $table->json('body');
                        $table->string('status')->default('draft');
                        $table->timestamps();{{ softDeletesColumn }}

                        // فهرس مركّب يبدأ بـ tenant_id دايماً
                        $table->index(['tenant_id', 'status']);
                        $table->index(['tenant_id', 'created_at']);
                    });
                }

                public function down(): void
                {
                    Schema::dropIfExists('{{ table }}');
                }
            };
            PHP;
    }

    private function factoryStub(): string
    {
        return <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Src\Contexts\{{ context }}\Database\Factories;

            use Illuminate\Database\Eloquent\Factories\Factory;
            use Src\Contexts\{{ context }}\Domain\Models\{{ model }};

            /** @extends Factory<{{ model }}> */
            final class {{ model }}Factory extends Factory
            {
                protected $model = {{ model }}::class;

                public function definition(): array
                {
                    $title = $this->faker->sentence(4);

                    return [
                        'title' => ['ar' => $title, 'en' => $title],
                        'body' => ['ar' => $this->faker->paragraph(), 'en' => $this->faker->paragraph()],
                        'status' => 'draft',
                    ];
                }
            }
            PHP;
    }

    private function testStub(): string
    {
        return <<<'PHP'
            <?php

            declare(strict_types=1);

            use Src\Contexts\{{ context }}\Domain\Models\{{ model }};
            use Src\Contexts\Tenancy\Domain\Models\Tenant;
            use Src\Support\Application\Contracts\TenantContext;

            it('ينشئ السجل بالمؤسسة الصحيحة', function (): void {
                actingAsRole('admin');

                ${{ modelVariable }} = {{ model }}::factory()->create();

                expect(${{ modelVariable }}->tenant_id)->toBe(currentTenant()->id);
            });

            it('لا يسرّب بيانات بين المؤسسات', function (): void {
                $a = currentTenant();
                $b = Tenant::factory()->create();

                app(TenantContext::class)->set($a->id);
                {{ model }}::factory()->count(2)->create();

                app(TenantContext::class)->set($b->id);
                {{ model }}::factory()->count(5)->create();

                app(TenantContext::class)->set($a->id);
                expect({{ model }}::count())->toBe(2);
            });

            it('يمنع المشاهد من الإنشاء', function (): void {
                $viewer = userWithRole('viewer');

                expect($viewer->can('create', {{ model }}::class))->toBeFalse();
            });
            PHP;
    }
}
