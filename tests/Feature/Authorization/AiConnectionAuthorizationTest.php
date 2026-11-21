<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Laravel\Passport\ClientRepository;
use Src\Contexts\Ai\Domain\Enums\AiAccessLevel;
use Src\Contexts\Ai\Domain\Models\AiConnection;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Authorization\ImpersonationContext;
use STS\FilamentImpersonate\Facades\Impersonation;

/*
|--------------------------------------------------------------------------
| مصفوفة صلاحيات ربط تطبيقات الذكاء الاصطناعي (docs/17 بند ٤ · docs/23)
|--------------------------------------------------------------------------
*/

function connectionFor(User $user, ?Tenant $tenant = null): AiConnection
{
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient('Claude', ['https://claude.ai/api/mcp/auth_callback'], confidential: false);

    $connection = new AiConnection;
    $connection->forceFill([
        'user_id' => $user->getKey(),
        'oauth_client_id' => $client->getKey(),
        'tenant_id' => ($tenant ?? currentTenant())->getKey(),
        'access' => AiAccessLevel::Read,
    ])->save();

    return $connection;
}

it('الربط على المؤسسة حسب الدور', function (string $role, bool $allowed): void {
    $user = userWithRole($role);

    expect($user->can('connect', [AiConnection::class, currentTenant()]))->toBe($allowed);
})->with([
    'admin' => ['admin', true],
    'editor' => ['editor', true],
    'viewer' => ['viewer', true],
]);

it('مايربطش على مؤسسة مش عضو فيها — حتى المدير العام', function (string $role): void {
    $user = userWithRole($role);
    $other = Tenant::factory()->create();

    expect($user->can('connect', [AiConnection::class, $other]))->toBeFalse();
})->with(['admin', 'super_admin']);

it('مايربطش لما المؤسسة قافلة الوصول — حتى المدير العام', function (string $role): void {
    $user = userWithRole($role);
    app(TenantContext::class)->forTenant(currentTenant()->getKey(), fn () => app(TenantSettings::class)->set('ai', 'mcp_enabled', false));

    $response = Gate::forUser($user)->inspect('connect', [AiConnection::class, currentTenant()]);

    expect($response->allowed())->toBeFalse()
        ->and($response->message())->toBe(__('authorization.denied.ai.disabled'));
})->with(['admin', 'super_admin']);

it('المركزية: فريق التشغيل بس، ولما تتفتح من الكونفيج', function (): void {
    $staff = centralUser('super_admin');

    expect($staff->can('connect', [AiConnection::class, null]))->toBeFalse();

    config(['ai.central_enabled' => true]);

    expect($staff->can('connect', [AiConnection::class, null]))->toBeTrue()
        ->and(userWithRole('admin')->can('connect', [AiConnection::class, null]))->toBeFalse();
});

it('روابط الكل في المؤسسة لمدير بس', function (string $role, bool $allowed): void {
    $connection = connectionFor(userWithRole('editor'));
    $user = userWithRole($role);

    expect($user->can('viewAny', AiConnection::class))->toBe($allowed)
        ->and($user->can('delete', $connection))->toBe($allowed);
})->with([
    'admin' => ['admin', true],
    'editor' => ['editor', false],
    'viewer' => ['viewer', false],
]);

it('صاحب الربط يقدر يفصله دايماً — مرة واحدة', function (): void {
    $user = userWithRole('viewer');
    $connection = connectionFor($user);

    expect($user->can('delete', $connection))->toBeTrue();

    $connection->forceFill(['revoked_at' => now()])->save();

    expect($user->can('delete', $connection))->toBeFalse();
});

it('مدير مؤسسة مايشوفش روابط مؤسسة تانية', function (): void {
    $other = Tenant::factory()->create();
    $connection = connectionFor(userWithRole('editor', $other), $other);

    $admin = userWithRole('admin');
    app(TenantContext::class)->set(currentTenant()->getKey());

    expect($admin->can('view', $connection))->toBeFalse()
        ->and($admin->can('delete', $connection))->toBeFalse();
});

it('المنتحل مايقدرش يربط AI باسم اللي بينتحله — وصول دايم بهوية غيره', function (): void {
    $admin = actingAsRole('admin');
    $target = userWithRole('editor');

    Impersonation::enter($admin, $target);
    app(ImpersonationContext::class)->markStarted();

    $response = Gate::forUser($target)->inspect('connect', [AiConnection::class, currentTenant()]);

    expect($response->allowed())->toBeFalse()
        ->and($response->message())->toBe(__('authorization.denied.while_impersonating'));
});
