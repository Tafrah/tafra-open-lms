<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Authorization\CentralRoles;

/*
|--------------------------------------------------------------------------
| أدوار فريق التشغيل على الفريق المركزي `0` (docs/24 بند ٢)
|--------------------------------------------------------------------------
*/

it('يرفض مؤسسة برقم صفر — الفريق ده محجوز للأدوار المركزية', function (): void {
    expect(fn () => Tenant::factory()->create(['id' => 0]))->toThrow(QueryException::class);
});

it('دور المؤسسة مابيدّيش صلاحية مركزية', function (): void {
    $admin = userWithRole('admin');

    app(TenantContext::class)->set(null);

    expect(Gate::forUser($admin)->allows('access.panel.central'))->toBeFalse()
        ->and(app(CentralRoles::class)->names($admin))->toBe([]);
});

it('المدير العام بيتجاوز الصلاحيات جوه أي مؤسسة من غير دور فيها', function (): void {
    $super = centralUser('super_admin');
    $tenant = currentTenant();

    app(TenantContext::class)->set($tenant->getKey());

    expect($super->getRoleNames()->all())->toBe([])
        ->and(Gate::forUser($super)->allows('access.panel.admin'))->toBeTrue();
});

it('صلاحيات الدعم الفني مركزية بس — مابتشتغلش جوه مؤسسة', function (): void {
    $support = centralUser('support');

    expect(Gate::forUser($support)->allows('access.panel.central'))->toBeTrue();

    app(TenantContext::class)->set(currentTenant()->getKey());

    expect(Gate::forUser($support->fresh())->allows('access.panel.central'))->toBeFalse();
});

it('أدوار فريق التشغيل مش ظاهرة في شاشة أدوار المؤسسة', function (): void {
    actingAsRole('admin');

    $names = RoleResource::getEloquentQuery()->pluck('name')->all();

    expect($names)->not->toContain('super_admin')
        ->and($names)->not->toContain('support')
        ->and($names)->toContain('admin');
});
