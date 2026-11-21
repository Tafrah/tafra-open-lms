<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Settings\Presentation\Filament\Pages\ManageTenantAppearance;
use Src\Contexts\Tenancy\Application\Actions\SuspendTenantAction;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource\Pages\EditTenant;
use Src\Contexts\Tenancy\Presentation\Filament\Pages\EditOrganization;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Authorization\CentralRoles;
use Src\Support\Infrastructure\Authorization\ImpersonationContext;

/*
|--------------------------------------------------------------------------
| حدود المؤسسات والتشغيل — مراجعة الأمان (docs/21 بند ٣٠ نقطة ١٥)
|--------------------------------------------------------------------------
| كل اختبار هنا اتكتب أحمر الأول من سيناريو مراجعة، وبعدين الإصلاح خضّره.
| أغلبها محتاج مستخدم في **أكتر من مؤسسة** — وده اللي المساعدات العادية
| مابتعملهوش، فالثغرات دي ماكانش ليها أي اختبار.
*/

/** مستخدم بدور مختلف في مؤسستين. */
function memberOfTwo(Tenant $first, string $firstRole, Tenant $second, string $secondRole): User
{
    $user = userWithRole($firstRole, $first);
    $user->tenants()->syncWithoutDetaching([$second->getKey() => ['joined_at' => now()]]);
    app(TenantContext::class)->forTenant((int) $second->getKey(), fn () => $user->assignRole($secondRole));

    return $user->fresh();
}

/** زي أول طلب في عامل جديد: مفيش مؤسسة ولا فريق مضبوط. */
function freshRequestContext(): void
{
    tenancy()->end();
    app(TenantContext::class)->set(null);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);
}

it('مدير في مؤسسة ومشاهد في التانية مايفتحش ملف التانية', function (): void {
    $b = Tenant::factory()->create();
    $user = memberOfTwo(currentTenant(), 'admin', $b, 'viewer');

    $this->actingAs($user);
    freshRequestContext();

    $this->get(EditOrganization::getUrl(tenant: $b))->assertNotFound();
});

it('مدير في مؤسسة ومشاهد في التانية مايفتحش إعدادات التانية', function (): void {
    $b = Tenant::factory()->create();
    $user = memberOfTwo(currentTenant(), 'admin', $b, 'viewer');
    $url = ManageTenantAppearance::getUrl(tenant: $b);

    $this->actingAs($user);
    freshRequestContext();

    $this->get($url)->assertForbidden();
});

it('الأدوار الافتراضية مشتركة بين كل المؤسسات فمايتعدّلوش من لوحة مؤسسة', function (string $role): void {
    $admin = userWithRole('admin');

    expect($admin->can('update', Role::findByName($role)))->toBeFalse()
        ->and($admin->can('delete', Role::findByName($role)))->toBeFalse();
})->with(['admin', 'editor', 'viewer']);

it('دور مخصص لمؤسسة تانية مخفي بـ 404', function (): void {
    $other = Tenant::factory()->create();

    $foreign = app(TenantContext::class)->forTenant((int) $other->getKey(), fn () => Role::create([
        'name' => 'foreign_reviewer',
        'guard_name' => config('authorization.guard'),
    ]));

    $admin = userWithRole('admin');

    expect((int) $foreign->getAttribute('tenant_id'))->toBe((int) $other->getKey())
        ->and(Gate::forUser($admin)->inspect('view', $foreign)->status())->toBe(404);
});

it('مدير مؤسسة مايقدرش ينتحل حد من فريق التشغيل حتى لو عضو معاه', function (): void {
    $admin = userWithRole('admin');
    $staff = userWithRole('support');

    expect(app(CentralRoles::class)->isStaff($staff))->toBeTrue()
        ->and($admin->can('impersonate', $staff))->toBeFalse();
});

it('انتحال من غير فريق التشغيل مايفتحش مؤسسة موقوفة', function (): void {
    $suspended = currentTenant();
    $active = Tenant::factory()->create();
    Tenant::factory()->create();

    $adminOfActive = userWithRole('admin', $active);
    $sharedMember = memberOfTwo($active, 'viewer', $suspended, 'viewer');
    app(SuspendTenantAction::class)->handle($suspended, 'مراجعة', centralUser('super_admin'));

    $this->actingAs($adminOfActive);
    app(ImpersonationContext::class)->enter($adminOfActive, $sharedMember);
    freshRequestContext();

    $this->get('/admin/t/'.$suspended->slug)->assertForbidden();
});

it('المؤسسة المعطّلة مابتتفتحش بالرابط المباشر لأعضائها', function (): void {
    $tenant = currentTenant();
    userWithRole('admin', $tenant);
    Tenant::factory()->create();

    $member = userWithRole('viewer', $tenant);
    $tenant->forceFill(['is_active' => false])->save();

    $this->actingAs($member);
    freshRequestContext();

    $this->get('/admin/t/'.$tenant->slug)->assertForbidden();
});

it('فورم المؤسسة مايطفّيش آخر مؤسسة نشطة', function (): void {
    $only = Tenant::factory()->create();
    actingAsCentral('super_admin');

    Livewire::test(EditTenant::class, ['record' => $only->getRouteKey()])
        ->fillForm(['is_active' => false])
        ->call('save');

    expect($only->fresh()->is_active)->toBeTrue();
});

it('اللفة على المؤسسات بتتخطى غير الجاهزة ومابتقفش عند فشل واحدة', function (): void {
    $first = Tenant::factory()->create();
    $pending = Tenant::factory()->create();
    $pending->forceFill(['provisioning_status' => Tenant::PROVISIONING_PENDING])->saveQuietly();
    $last = Tenant::factory()->create();

    $visited = [];

    app(TenantContext::class)->forEachTenant(function (Tenant $tenant) use (&$visited, $first): void {
        $visited[] = (int) $tenant->getKey();

        if ($tenant->is($first)) {
            throw new RuntimeException('مؤسسة بايظة');
        }
    });

    expect($visited)->toContain((int) $first->getKey(), (int) $last->getKey())
        ->not->toContain((int) $pending->getKey())
        ->and(app(TenantContext::class)->id())->toBeNull();
});

it('مسارات ميجريشنز المؤسسات مابتتكررش', function (): void {
    $paths = (array) config('tenancy.migration_parameters.--path', []);

    expect($paths)->not->toBeEmpty()
        ->and(array_values(array_unique($paths)))->toBe(array_values($paths));
});
