<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Audit\Domain\Models\ActivityLog;
use Src\Contexts\Identity\Application\Actions\ForceLogoutAction;
use Src\Support\Infrastructure\Authorization\ImpersonationContext;
use STS\FilamentImpersonate\Facades\Impersonation;

it('ينهي جلسة الانتحال تلقائياً بعد المدة القصوى', function (): void {
    $admin = actingAsRole('super_admin');
    $target = userWithRole('viewer');

    Impersonation::enter($admin, $target);
    app(ImpersonationContext::class)->markStarted();

    expect(app(ImpersonationContext::class)->isActive())->toBeTrue();

    $this->travel(31)->minutes();

    $this->get('/admin/t/'.currentTenant()->slug)->assertRedirect();

    expect(app(ImpersonationContext::class)->isActive())->toBeFalse()
        ->and(ActivityLog::query()->where('description', 'impersonation_expired')->exists())->toBeTrue();

    $this->travelBack();
});

it('لا ينهي الجلسة قبل انتهاء المدة', function (): void {
    $admin = actingAsRole('super_admin');
    $target = userWithRole('viewer');

    Impersonation::enter($admin, $target);
    app(ImpersonationContext::class)->markStarted();

    $this->travel(5)->minutes();

    $this->get('/admin/t/'.currentTenant()->slug);

    expect(app(ImpersonationContext::class)->isActive())->toBeTrue();

    $this->travelBack();
});

it('يقفل العمليات الحساسة أثناء الانتحال', function (): void {
    $admin = actingAsRole('super_admin');
    $target = userWithRole('viewer');
    $another = userWithRole('viewer');

    // قبل الانتحال: مسموح
    expect(Gate::forUser($admin)->allows('delete', $another))->toBeTrue();

    Impersonation::enter($admin, $target);
    app(ImpersonationContext::class)->markStarted();

    // أثناء الانتحال: مقفول بسبب واضح
    $response = Gate::forUser($admin)->inspect('delete', $another);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(__('authorization.denied.while_impersonating'));
});

it('يمنع انتحال شخصية مستخدم موقوف', function (): void {
    $admin = actingAsRole('super_admin');
    $suspended = userWithRole('viewer');
    $suspended->forceFill(['is_active' => false, 'status' => 'suspended'])->save();

    $response = Gate::forUser($admin)->inspect('impersonate', $suspended->fresh());

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(__('authorization.denied.user.inactive_target'));
});

it('يمنع انتحال المستخدم لنفسه', function (): void {
    $admin = actingAsRole('super_admin');

    $response = Gate::forUser($admin)->inspect('impersonate', $admin);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(__('authorization.denied.self_target'));
});

it('ينهي كل الجلسات ويسجّلها', function (): void {
    $admin = actingAsRole('admin');
    $target = userWithRole('viewer');

    app(ForceLogoutAction::class)->handle($target, actor: $admin);

    expect(ActivityLog::query()->where('description', 'force_logout')->exists())->toBeTrue();
});

it('يمنع إنهاء المستخدم لجلساته من شاشة الإدارة', function (): void {
    $admin = actingAsRole('admin');

    $response = Gate::forUser($admin)->inspect('forceLogout', $admin);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(__('authorization.denied.self_target'));
});
