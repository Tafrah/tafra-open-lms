<?php

declare(strict_types=1);

use FutureCode\Onboarding\Facades\Onboarding;
use FutureCode\Onboarding\Models\OnboardingProgress;
use FutureCode\Onboarding\Pages\OnboardingAnalytics;
use FutureCode\Onboarding\Pages\SetupWizard;
use FutureCode\Onboarding\Tours\Tour;
use FutureCode\Onboarding\Widgets\OnboardingChecklistWidget;
use Livewire\Livewire;
use Src\Contexts\Identity\Application\Onboarding\InviteTeamStep;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Presentation\Tours\UsersListTour;
use Src\Contexts\Settings\Application\Onboarding\ConfigureBrandingStep;
use Src\Contexts\Settings\Domain\Settings\AppearanceSettings;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

/*
|--------------------------------------------------------------------------
| الخطوات بتقرا الحالة الحقيقية — مش علامة يدوية (docs/15 بند ٤)
|--------------------------------------------------------------------------
*/

it('يقرأ اكتمال خطوة العلامة من حالة النظام الفعلية', function (): void {
    actingAsRole('super_admin');

    $step = app(ConfigureBrandingStep::class);

    expect($step->isComplete())->toBeFalse();

    $appearance = app(AppearanceSettings::class);
    $appearance->primary_color = '#A32E3C';
    $appearance->save();

    // نفس الكائن، حالة جديدة — مفيش علامة محفوظة في أي مكان
    expect(app(ConfigureBrandingStep::class)->isComplete())->toBeTrue();
});

it('يرجع خطوة الفريق لغير مكتملة لو المستخدم لوحده', function (): void {
    actingAsRole('admin');

    expect(app(InviteTeamStep::class)->isComplete())->toBeFalse();

    $colleague = User::factory()->create();
    $colleague->tenants()->attach(currentTenant()->getKey(), ['joined_at' => now()]);

    expect(app(InviteTeamStep::class)->isComplete())->toBeTrue();
});

it('يخفي الخطوة عن مين مالوش قدرتها', function (): void {
    $viewer = userWithRole('viewer');   // مالوش access.organization_profile
    $admin = userWithRole('admin');

    $viewerKeys = Onboarding::stepsFor($viewer, 'admin')->map(fn ($step): string => $step::key())->all();
    $adminKeys = Onboarding::stepsFor($admin, 'admin')->map(fn ($step): string => $step::key())->all();

    expect($viewerKeys)->not->toContain('organization')
        ->and($viewerKeys)->toContain('team')
        ->and($adminKeys)->toContain('organization');
});

it('كل لوحة بتعرض خطواتها بس', function (): void {
    $super = centralUser('super_admin');

    $central = Onboarding::stepsFor($super, 'central')->map(fn ($step): string => $step::key())->all();
    $admin = Onboarding::stepsFor($super, 'admin')->map(fn ($step): string => $step::key())->all();

    expect($central)->toContain('branding', 'mail', 'storage', 'first_tenant')
        ->and($central)->not->toContain('team', 'organization')
        ->and($admin)->toContain('team', 'organization')
        ->and($admin)->not->toContain('mail', 'first_tenant');
});

it('بيكتشف الخطوات والجولات من السياقات من غير قايمة يدوية', function (): void {
    expect(config('fc-onboarding.steps'))->toBe([])
        ->and(config('fc-onboarding.tours.registered'))->toBe([])
        ->and(Onboarding::steps()->map(fn ($step): string => $step::key())->all())->toContain('organization', 'first_tenant')
        ->and(Onboarding::tours()->map(fn (Tour $tour): string => $tour::key())->all())->toContain('users.list', 'tenancy.tenants');
});

it('يحسب نسبة الاكتمال من الخطوات المرئية للمستخدم', function (): void {
    $viewer = actingAsRole('viewer');

    // المشاهد بيشوف خطوة واحدة بس (الفريق) وهي مش مكتملة
    expect(Onboarding::completionFor($viewer))->toBe(0.0);

    $colleague = User::factory()->create();
    $colleague->tenants()->attach(currentTenant()->getKey(), ['joined_at' => now()]);

    expect(Onboarding::completionFor($viewer->fresh()))->toBe(1.0);
});

it('لا يعتبر خطوة الفريق مكتملة من غير مستخدم مسجّل', function (): void {
    $tenant = currentTenant();
    app(TenantContext::class)->set($tenant->getKey());

    User::factory()->create()->tenants()->attach($tenant->getKey(), ['joined_at' => now()]);

    // مفيش auth()->id() — الخطوة لازم ترجع false بدل ما تعدّ المستخدم نفسه
    expect(app(InviteTeamStep::class)->isComplete())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| التقدّم في قاعدة البيانات مش localStorage
|--------------------------------------------------------------------------
*/

it('يخزّن التقدّم في قاعدة البيانات لكل مستخدم ومؤسسة', function (): void {
    $user = actingAsRole('admin');

    $progress = Onboarding::progress();

    expect($progress)->toBeInstanceOf(OnboardingProgress::class)
        ->and($progress->user_id)->toBe($user->getKey())
        ->and($progress->tenant_id)->toBe(currentTenant()->getKey());

    // التقدّم مركزي — في وضع multi الاتصال الافتراضي بيبقى قاعدة المؤسسة
    $this->assertDatabaseHas('onboarding_progress', [
        'user_id' => $user->getKey(),
        'tenant_id' => currentTenant()->getKey(),
    ], TenancyMode::centralConnection());
});

it('يسجّل اكتمال الجولة ومايعيدهاش', function (): void {
    $user = actingAsRole('admin');
    $tour = app(UsersListTour::class);

    expect(Onboarding::shouldAutoStart($tour, $user))->toBeTrue();

    Onboarding::progress()->markTourCompleted($tour::key());

    expect(Onboarding::progress()->hasCompletedTour($tour::key()))->toBeTrue()
        ->and(Onboarding::shouldAutoStart($tour, $user))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| الجولات
|--------------------------------------------------------------------------
*/

it('يحدّ الجولة بخمس خطوات كحد أقصى', function (): void {
    actingAsRole('admin');

    foreach (Onboarding::tours() as $tour) {
        expect(count($tour->payload()))->toBeLessThanOrEqual(Tour::MAX_STEPS);
    }
});

it('يتخطى خطوات الجولة اللي المستخدم مالوش قدرتها', function (): void {
    $viewer = userWithRole('viewer');
    $this->actingAs($viewer);

    $withViewer = count(app(UsersListTour::class)->payload());

    $this->actingAs(userWithRole('super_admin'));
    $withSuper = count(app(UsersListTour::class)->payload());

    expect($withSuper)->toBeGreaterThanOrEqual($withViewer);
});

/*
|--------------------------------------------------------------------------
| الودجت والصفحات
|--------------------------------------------------------------------------
*/

it('يعرض ودجت قائمة المهام للمدير ويخفيها بعد الإخفاء', function (): void {
    actingAsRole('admin');

    expect(OnboardingChecklistWidget::canView())->toBeTrue();

    Onboarding::progress()->dismiss();

    expect(OnboardingChecklistWidget::canView())->toBeFalse();
});

it('يعرض معالج الإعداد ويتنقّل بين الخطوات', function (): void {
    actingAsRole('super_admin');

    Livewire::test(SetupWizard::class)
        ->assertOk()
        ->assertSet('currentIndex', 0)
        ->call('next')
        ->assertSet('currentIndex', 1)
        ->call('previous')
        ->assertSet('currentIndex', 0);
});

it('يسجّل اكتمال المعالج', function (): void {
    actingAsRole('super_admin');

    Livewire::test(SetupWizard::class)->call('finish');

    expect(Onboarding::progress()->fresh()->wizard_completed_at)->not->toBeNull();
});

it('يقفل صفحة المقاييس على صاحب الصلاحية', function (): void {
    actingAsRole('viewer');
    expect(OnboardingAnalytics::canAccess())->toBeFalse();

    actingAsRole('super_admin');
    expect(OnboardingAnalytics::canAccess())->toBeTrue();
});

it('يحسب نقطة التوقّف في المقاييس', function (): void {
    actingAsRole('super_admin');

    OnboardingProgress::query()->create([
        'user_id' => userWithRole('admin')->getKey(),
        'tenant_id' => currentTenant()->getKey(),
        'completed_steps' => ['organization'],
        'completed_tours' => [],
    ]);

    $breakdown = Livewire::test(OnboardingAnalytics::class)->instance()->stepBreakdown();

    expect($breakdown)->not->toBeEmpty()
        ->and($breakdown->firstWhere('key', 'organization')['completed'])->toBeGreaterThanOrEqual(1);
});
