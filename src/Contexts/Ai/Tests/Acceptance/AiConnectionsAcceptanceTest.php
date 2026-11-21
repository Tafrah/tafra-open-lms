<?php

declare(strict_types=1);

use Laravel\Passport\ClientRepository;
use Livewire\Livewire;
use Src\Contexts\Ai\Domain\Enums\AiAccessLevel;
use Src\Contexts\Ai\Domain\Models\AiConnection;
use Src\Contexts\Ai\Presentation\Filament\Pages\AiConnections;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Tenancy\Domain\Models\Tenant;

/*
|--------------------------------------------------------------------------
| «تطبيقات الذكاء الاصطناعي المربوطة» — معايير القبول (docs/23 بند ٩)
|--------------------------------------------------------------------------
*/

function aiConnection(User $user, ?Tenant $tenant = null, string $client = 'Claude'): AiConnection
{
    $oauth = app(ClientRepository::class)->createAuthorizationCodeGrantClient($client, ['https://claude.ai/api/mcp/auth_callback'], confidential: false);

    $connection = new AiConnection;
    $connection->forceFill([
        'user_id' => $user->getKey(),
        'oauth_client_id' => $oauth->getKey(),
        'tenant_id' => ($tenant ?? currentTenant())->getKey(),
        'access' => AiAccessLevel::Read,
    ])->save();

    return $connection;
}

it('العضو بيشوف رابط الخادم وخطوات الربط وحالة فاضية خاصة بالشاشة', function (): void {
    actingAsRole('editor');

    $this->get(AiConnections::getUrl())
        ->assertOk()
        ->assertSee(url('/mcp'))
        ->assertSee(__('ai::ai.connections.connect.steps.claude'))
        ->assertSee(__('ai::ai.connections.empty.heading'));
});

it('العضو بيشوف روابطه هو بس', function (): void {
    $editor = actingAsRole('editor');
    $mine = aiConnection($editor);
    $theirs = aiConnection(userWithRole('editor'));

    Livewire::test(AiConnections::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs]);
});

it('الفصل بيقفل الربط وتوكنزه فوراً', function (): void {
    $editor = actingAsRole('editor');
    $connection = aiConnection($editor);

    Livewire::test(AiConnections::class)
        ->callTableAction('revoke', $connection)
        ->assertHasNoTableActionErrors();

    expect($connection->fresh()->isActive())->toBeFalse();
});

it('المدير بيشوف روابط أعضاء المؤسسة ويقدر يفصلها — ومؤسسة تانية لا', function (): void {
    $member = userWithRole('editor');
    $memberConnection = aiConnection($member);

    $other = Tenant::factory()->create();
    $outsider = userWithRole('editor', $other);
    $outsiderConnection = aiConnection($outsider, $other);

    actingAsRole('admin');

    Livewire::test(AiConnections::class)
        ->filterTable('scope', 'organization')
        ->assertCanSeeTableRecords([$memberConnection])
        ->assertCanNotSeeTableRecords([$outsiderConnection])
        ->callTableAction('revoke', $memberConnection);

    expect($memberConnection->fresh()->isActive())->toBeFalse()
        ->and($outsiderConnection->fresh()->isActive())->toBeTrue();
});

it('المحرر مايقدرش يفصل ربط حد تاني حتى بالنداء المباشر', function (): void {
    $connection = aiConnection(userWithRole('editor'));
    $editor = actingAsRole('editor');

    expect($editor->can('delete', $connection))->toBeFalse();
});

it('اللي اتشالت منه الصلاحية بعد ما ربط يقدر يفصل — ومن غير ربط الصفحة مقفولة', function (): void {
    $user = actingWithPermissions(['access.panel.admin']);

    $this->get(AiConnections::getUrl())->assertForbidden();

    $connection = aiConnection($user);

    $this->get(AiConnections::getUrl())->assertOk()->assertDontSee(__('ai::ai.connections.connect.heading'));

    Livewire::test(AiConnections::class)->callTableAction('revoke', $connection);

    expect($connection->fresh()->isActive())->toBeFalse();
});

it('خطوات الربط بتختفي لما المؤسسة تقفل الوصول', function (): void {
    actingAsRole('admin');
    app(TenantSettings::class)->set('ai', 'mcp_enabled', false);

    $this->get(AiConnections::getUrl())
        ->assertOk()
        ->assertDontSee(__('ai::ai.connections.connect.heading'));
});
