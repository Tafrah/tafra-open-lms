<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Src\Contexts\Audit\Domain\Models\ActivityLog;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Application\Actions\ImpersonateTenantAction;
use Src\Contexts\Tenancy\Application\Actions\SuspendTenantAction;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource\Pages\CreateTenant;
use Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource\Pages\ListTenants;
use Src\Support\Infrastructure\Authorization\ImpersonationContext;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

/*
|--------------------------------------------------------------------------
| معايير قبول اللوحة المركزية (docs/24 بند ٨)
|--------------------------------------------------------------------------
*/

/** هل قاعدة المؤسسة موجودة على الخادم؟ (وضع multi) */
function tenantDatabaseExists(Tenant $tenant): bool
{
    $name = $tenant->database()->getName();

    return DB::connection((string) config('tenancy.database.central_connection'))
        ->table('pg_database')
        ->where('datname', $name)
        ->exists();
}

it('اللوحة المركزية منفصلة ومن غير مؤسسة في الرابط', function (): void {
    actingAsCentral('super_admin');

    $this->get('/central')->assertOk();
});

/*
 * `Authenticate` ليه أولوية في Laravel وبيشتغل **قبل** ميدلوير السياق.
 * لو السياق ماكانش في قايمة الأولوية، `canAccessPanel` كان بيتسأل على
 * فريق فاضي (بداية أي عملية PHP جديدة) → 403 للكل في الإنتاج، والاختبارات
 * خضرا لأن الـ helpers سايبين الفريق مضبوط (docs/21 بند ٣٠).
 */
it('كل لوحة بتضبط سياقها قبل فحص الدخول', function (string $panel): void {
    if ($panel === 'central') {
        actingAsCentral('support');
        $url = '/central';
    } else {
        actingAsRole('admin');
        $url = '/admin/t/'.currentTenant()->slug;
    }

    // زي أول طلب في عامل جديد: مفيش فريق ولا tenancy
    tenancy()->end();
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->get($url)->assertOk();
})->with(['admin', 'central']);

it('مستخدم مؤسسة حتى المدير مايقدرش يفتح /central', function (string $role): void {
    actingAsRole($role);

    $this->get('/central')->assertForbidden();
})->with(['admin', 'viewer']);

it('إنشاء مؤسسة من /central بيجهّزها كاملة', function (): void {
    actingAsCentral('super_admin');

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name' => ['ar' => 'مؤسسة الاختبار', 'en' => 'Test Org'],
            'slug' => 'test-org',
            'primary_color' => '#12454F',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $tenant = Tenant::query()->where('slug', 'test-org')->firstOrFail();

    expect($tenant->isProvisioned())->toBeTrue()
        ->and($tenant->users()->count())->toBe(0)
        ->and(ActivityLog::query()->where('description', 'tenant_provisioned')->exists())->toBeTrue();

    if (TenancyMode::isMulti()) {
        expect(tenantDatabaseExists($tenant))->toBeTrue();

        $tenant->run(function (): void {
            expect(DB::table('migrations')->count())->toBeGreaterThan(0);
        });
    }
});

it('قائمة المؤسسات بتعرض كل المؤسسات لفريق التشغيل', function (): void {
    $tenants = Tenant::factory()->count(3)->create();

    actingAsCentral('support');

    Livewire::test(ListTenants::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords($tenants);
});

it('الحذف الناعم مابيمسحش قاعدة المؤسسة، والنهائي بيمسحها', function (): void {
    $tenant = Tenant::factory()->create();

    $tenant->delete();

    expect(tenantDatabaseExists($tenant))->toBeTrue();

    $tenant->forceDelete();

    expect(tenantDatabaseExists($tenant))->toBeFalse();
})->skip(fn (): bool => ! TenancyMode::isMulti(), 'قاعدة لكل مؤسسة بس');

it('المؤسسة الموقوفة بتختفي من التبديل ورابطها بيعرض صفحة الإيقاف', function (): void {
    $admin = actingAsRole('admin');
    $tenant = currentTenant();
    Tenant::factory()->create();   // مش آخر مؤسسة نشطة

    app(SuspendTenantAction::class)->handle($tenant, 'تأخر السداد', centralUser('super_admin'));

    expect($admin->fresh()->getTenants(Filament\Facades\Filament::getPanel('admin'))->pluck('id'))
        ->not->toContain($tenant->getKey());

    $this->actingAs($admin->fresh())
        ->get('/admin/t/'.$tenant->slug)
        ->assertForbidden()
        ->assertSee(__('tenancy::tenancy.tenant.suspended_page.title'))
        ->assertSee('تأخر السداد');
});

it('الانتحال من /central بيدخل لوحة المؤسسة كمديرها وبيتسجّل', function (): void {
    $tenant = currentTenant();
    $tenantAdmin = userWithRole('admin', $tenant);
    $support = actingAsCentral('support');

    $target = app(ImpersonateTenantAction::class)->handle($tenant, $support);

    expect($target->is($tenantAdmin))->toBeTrue()
        ->and(app(ImpersonationContext::class)->isActive())->toBeTrue()
        ->and(auth()->id())->toBe($tenantAdmin->getKey())
        ->and(ActivityLog::query()->where('description', 'tenant_impersonated')->where('causer_id', $support->getKey())->exists())->toBeTrue();

    $this->get('/admin/t/'.$tenant->slug)->assertOk();
});

it('إنهاء الانتحال بيرجّع فريق التشغيل للوحة المركزية', function (): void {
    $tenant = currentTenant();
    userWithRole('admin', $tenant);
    $support = actingAsCentral('support');

    app(ImpersonateTenantAction::class)->handle($tenant, $support);

    $this->get(route('filament-impersonate.leave'))->assertRedirect('/central');

    expect(app(ImpersonationContext::class)->isActive())->toBeFalse()
        ->and(auth()->id())->toBe($support->getKey());
});

it('انتهاء مدة الانتحال بيرجّع فريق التشغيل للوحة المركزية', function (): void {
    $tenant = currentTenant();
    userWithRole('admin', $tenant);
    $support = actingAsCentral('support');

    app(ImpersonateTenantAction::class)->handle($tenant, $support);

    $this->travel(31)->minutes();

    $this->get('/admin/t/'.$tenant->slug)->assertRedirect('/central');

    $this->travelBack();
});

it('الانتحال مسموح على مؤسسة موقوفة عشان الدعم يشوف المشكلة', function (): void {
    $tenant = currentTenant();
    userWithRole('admin', $tenant);
    Tenant::factory()->create();

    $support = actingAsCentral('support');
    app(SuspendTenantAction::class)->handle($tenant, 'مراجعة', centralUser('super_admin'));

    app(ImpersonateTenantAction::class)->handle($tenant->fresh(), $support);

    $this->get('/admin/t/'.$tenant->slug)->assertOk();
});

it('مايدخلش مؤسسة مالهاش مدير نشط', function (): void {
    $tenant = currentTenant();
    $support = centralUser('support');

    expect(fn () => app(ImpersonateTenantAction::class)->handle($tenant, $support))
        ->toThrow(ValidationException::class);
});

it('فريق التشغيل مش عضو في أي مؤسسة', function (): void {
    $super = centralUser('super_admin');

    expect(User::query()->ofTenant(currentTenant()->getKey())->whereKey($super->getKey())->exists())->toBeFalse();
});
