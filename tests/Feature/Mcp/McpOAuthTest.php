<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Spatie\Permission\PermissionRegistrar;
use Src\Contexts\Ai\Application\Actions\AuthorizeAiConnectionAction;
use Src\Contexts\Ai\Application\Actions\RevokeAiConnectionAction;
use Src\Contexts\Ai\Application\DataObjects\AuthorizeAiConnectionData;
use Src\Contexts\Ai\Domain\Enums\AiAccessLevel;
use Src\Contexts\Ai\Domain\Models\AiConnection;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Settings\Domain\Settings\SecuritySettings;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/*
|--------------------------------------------------------------------------
| OAuth لتطبيقات الذكاء الاصطناعي — Claude وChatGPT (docs/23 بند ٤)
|--------------------------------------------------------------------------
| الاكتشاف، التسجيل الديناميكي، الموافقة، والحاجز بين التوكن والأدوات.
*/

beforeEach(fn () => usePassportTestKeys());

// جدول `settings` مابيتفرّغش في وضع multi (TestCase::$exceptTables) — مفتاح
// النظام اللي اتقفل في اختبار لازم يرجع، وإلا كل اللي بعده بيتقفل
afterEach(function (): void {
    $settings = app(SecuritySettings::class);

    if (! $settings->mcp_enabled) {
        $settings->fill(['mcp_enabled' => true])->save();
    }
});

const CLAUDE_CALLBACK = 'https://claude.ai/api/mcp/auth_callback';

function oauthClient(string $name = 'Claude'): Client
{
    return app(ClientRepository::class)->createAuthorizationCodeGrantClient($name, [CLAUDE_CALLBACK], confidential: false);
}

function connect(User $user, Client $client, ?Tenant $tenant, AiAccessLevel $access = AiAccessLevel::Write): AiConnection
{
    $connection = new AiConnection;
    $connection->forceFill([
        'user_id' => $user->getKey(),
        'oauth_client_id' => $client->getKey(),
        'tenant_id' => $tenant?->getKey(),
        'access' => $access,
    ])->save();

    return $connection;
}

/** نداء JSON-RPC على `/mcp` بتوكن OAuth للعميل ده. */
function mcpHttp(User $user, Client $client, string $method = 'tools/list', array $params = []): TestResponse
{
    Passport::actingAs($user, ['mcp:use'], 'api', $client);

    return test()->postJson('/'.config('ai.path'), [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => $method,
        'params' => $params,
    ], ['Accept' => 'application/json, text/event-stream']);
}

// ══════════════ الاكتشاف ══════════════

it('بيعلن المورد المحمي برابط الخادم بالظبط', function (): void {
    $this->getJson('/.well-known/oauth-protected-resource/mcp')
        ->assertOk()
        ->assertJsonPath('resource', url('/mcp'))
        ->assertJsonPath('authorization_servers.0', url('/'))
        ->assertJsonPath('scopes_supported', ['mcp:use']);

    $this->getJson('/.well-known/oauth-protected-resource/anything-else')->assertNotFound();
});

it('بيعلن خادم التفويض بالحقول اللي Claude وChatGPT بيشترطوها', function (): void {
    $this->getJson('/.well-known/oauth-authorization-server')
        ->assertOk()
        ->assertJsonPath('issuer', url('/'))
        ->assertJsonPath('registration_endpoint', route('ai.oauth.register'))
        ->assertJsonPath('code_challenge_methods_supported', ['S256'])
        ->assertJsonPath('token_endpoint_auth_methods_supported', ['none'])
        ->assertJsonPath('grant_types_supported', ['authorization_code', 'refresh_token']);
});

it('النداء من غير توكن بيرجع 401 بعنوان اكتشاف المورد', function (): void {
    $response = $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);

    $response->assertUnauthorized();

    expect($response->headers->get('WWW-Authenticate'))
        ->toContain('Bearer')
        ->toContain('resource_metadata=');
});

// ══════════════ التسجيل الديناميكي ══════════════

it('بيسجّل Claude وChatGPT وبيرفض أي رابط رجوع تاني', function (string $uri, bool $allowed): void {
    $response = $this->postJson('/oauth/register', ['client_name' => 'Test', 'redirect_uris' => [$uri]]);

    $allowed ? $response->assertCreated()->assertJsonPath('token_endpoint_auth_method', 'none') : $response->assertStatus(400);
})->with([
    'claude' => [CLAUDE_CALLBACK, true],
    'chatgpt' => ['https://chatgpt.com/connector_platform_oauth_redirect', true],
    'claude code (localhost)' => ['http://localhost:33418/callback', true],
    'غريب' => ['https://evil.example/callback', false],
    'نطاق شبيه' => ['https://claude.ai.evil.example/callback', false],
    'scheme مخصص' => ['cursor://anysphere.cursor-retrieval/oauth', false],
]);

it('التسجيل محدود المعدّل', function (): void {
    config(['ai.oauth.registrations_per_minute' => 2]);

    foreach (range(1, 2) as $_) {
        $this->postJson('/oauth/register', ['redirect_uris' => [CLAUDE_CALLBACK]])->assertCreated();
    }

    $this->postJson('/oauth/register', ['redirect_uris' => [CLAUDE_CALLBACK]])->assertTooManyRequests();
});

// ══════════════ الحاجز: توكن + ربط نشط + مؤسسة + مفاتيح ══════════════

it('توكن سليم بربط نشط بيشوف الأدوات بصلاحيات صاحبه', function (): void {
    $user = userWithRole('editor');
    $client = oauthClient();
    connect($user, $client, currentTenant());

    $tools = array_column(mcpHttp($user, $client)->assertOk()->json('result.tools'), 'name');

    expect($tools)->toContain('describe_system', 'announcements_create')
        ->not->toContain('announcements_publish');

    expect(AiConnection::query()->firstOrFail()->last_used_at)->not->toBeNull();
});

it('توكن من غير ربط بيترفض 401 عشان العميل يطلب موافقة', function (): void {
    $user = userWithRole('admin');

    mcpHttp($user, oauthClient())->assertUnauthorized();
});

it('الربط الملغي بيقفل فوراً — والتوكنز بتتلغي معاه', function (): void {
    $user = userWithRole('admin');
    $client = oauthClient();
    $connection = connect($user, $client, currentTenant());

    $token = Token::query()->forceCreate([
        'id' => Str::random(80),
        'user_id' => $user->getKey(),
        'client_id' => $client->getKey(),
        'scopes' => ['mcp:use'],
        'revoked' => false,
        'expires_at' => now()->addHour(),
    ]);

    app(RevokeAiConnectionAction::class)->handle($connection, $user);

    expect($token->fresh()->revoked)->toBeTrue();

    mcpHttp($user, $client)->assertUnauthorized();
});

it('ربط القراءة مابيشوفش أدوات كتابة', function (): void {
    $user = userWithRole('admin');
    $client = oauthClient();
    connect($user, $client, currentTenant(), AiAccessLevel::Read);

    $tools = array_column(mcpHttp($user, $client)->assertOk()->json('result.tools'), 'name');

    expect($tools)->toContain('announcements_list')->not->toContain('announcements_create');
});

it('مفاتيح الإيقاف التلاتة بتقفل فوراً من غير ما تلغي الربط', function (string $switch): void {
    $user = userWithRole('admin');
    $client = oauthClient();
    connect($user, $client, currentTenant());

    match ($switch) {
        'environment' => config(['ai.enabled' => false]),
        'system' => tap(app(SecuritySettings::class), fn (SecuritySettings $settings) => $settings->fill(['mcp_enabled' => false])->save()),
        'organization' => app(TenantContext::class)->forTenant(currentTenant()->getKey(), fn () => app(TenantSettings::class)->set('ai', 'mcp_enabled', false)),
    };

    mcpHttp($user, $client)->assertForbidden();

    expect(AiConnection::query()->firstOrFail()->isActive())->toBeTrue();
})->with(['environment', 'system', 'organization']);

it('المؤسسة تقدر تقفل الكتابة للكل حتى لو الربط «قراءة وتعديل»', function (): void {
    $user = userWithRole('admin');
    $client = oauthClient();
    connect($user, $client, currentTenant());

    app(TenantContext::class)->forTenant(currentTenant()->getKey(), fn () => app(TenantSettings::class)->set('ai', 'allow_writes', false));

    $tools = array_column(mcpHttp($user, $client)->assertOk()->json('result.tools'), 'name');

    expect($tools)->not->toContain('announcements_create');
});

it('العضو اللي اتشال من المؤسسة بيتقفل حتى لو الربط لسه موجود', function (): void {
    $user = userWithRole('admin');
    $client = oauthClient();
    connect($user, $client, currentTenant());

    $user->tenants()->detach(currentTenant()->getKey());

    mcpHttp($user, $client)->assertForbidden();
});

it('المؤسسة الموقوفة بتتقفل', function (): void {
    $user = userWithRole('admin');
    $client = oauthClient();
    connect($user, $client, currentTenant());

    currentTenant()->forceFill(['suspended_at' => now()])->save();

    mcpHttp($user, $client)->assertForbidden();
});

it('اللوحة المركزية مقفولة عن طريق AI افتراضياً', function (): void {
    $staff = centralUser('super_admin');
    $client = oauthClient();
    connect($staff, $client, null);

    mcpHttp($staff, $client)->assertForbidden();

    config(['ai.central_enabled' => true]);

    $tools = array_column(mcpHttp($staff, $client)->assertOk()->json('result.tools'), 'name');

    expect($tools)->toContain('tenants_list', 'tenants_suspend')
        ->not->toContain('tenants_impersonate', 'tenants_force_delete', 'announcements_list');
});

// ══════════════ الموافقة ══════════════

it('الضيف على شاشة الموافقة بيروح لدخول اللوحة', function (): void {
    $client = oauthClient();

    $this->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => CLAUDE_CALLBACK,
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => 'xyz',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', Str::random(64), true)), '+/', '-_'), '='),
        'code_challenge_method' => 'S256',
    ]))->assertRedirect(route('filament.admin.auth.login'));
});

it('الرحلة كاملة: موافقة بمؤسسة ← كود ← توكن بـ PKCE ← أدوات', function (): void {
    $user = userWithRole('editor');
    $other = Tenant::factory()->create();
    $client = oauthClient();
    $verifier = Str::random(64);

    $authorize = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => CLAUDE_CALLBACK,
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => 'state-123',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
        'code_challenge_method' => 'S256',
    ]));

    // المؤسسة اللي هو عضو فيها بس — مش التانية
    $authorize->assertOk()
        ->assertSee(currentTenant()->name)
        ->assertDontSee($other->name)
        ->assertSee(__('ai::ai.consent.points.never_more'));

    // فورم معدّل بمؤسسة مش عضو فيها بيترفض
    $this->post(route('ai.oauth.consent'), [
        'auth_token' => session('authToken'),
        'tenant' => (string) $other->getKey(),
        'access' => 'write',
    ])->assertForbidden();

    expect(AiConnection::query()->count())->toBe(0);

    $approve = $this->post(route('ai.oauth.consent'), [
        'auth_token' => session('authToken'),
        'tenant' => (string) currentTenant()->getKey(),
        'access' => 'write',
    ]);

    $approve->assertRedirect();
    parse_str((string) parse_url((string) $approve->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($query['state'])->toBe('state-123')
        ->and(AiConnection::query()->firstOrFail())
        ->tenant_id->toBe(currentTenant()->getKey())
        ->access->toBe(AiAccessLevel::Write);

    $token = $this->post('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $client->getKey(),
        'redirect_uri' => CLAUDE_CALLBACK,
        'code_verifier' => $verifier,
        'code' => $query['code'],
    ])->assertOk()->json();

    expect($token)->toHaveKeys(['access_token', 'refresh_token', 'expires_in'])
        ->and($token['expires_in'])->toBeLessThanOrEqual(config('ai.oauth.access_token_minutes') * 60);

    auth()->forgetGuards();

    $tools = $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'], [
        'Authorization' => 'Bearer '.$token['access_token'],
        'Accept' => 'application/json, text/event-stream',
    ])->assertOk()->json('result.tools');

    expect(array_column($tools, 'name'))->toContain('announcements_create');
});

it('موافقة على مؤسسة تانية لنفس العميل بتلغي توكنز الأولى', function (): void {
    $user = userWithRole('admin');
    $second = Tenant::factory()->create();
    $user->tenants()->attach($second->getKey(), ['joined_at' => now()]);
    // الصلاحية بتتسأل على فريق المؤسسة الجديدة — لازم دور هناك كمان
    app(TenantContext::class)->forTenant($second->getKey(), fn () => $user->assignRole('admin'));
    $client = oauthClient();
    connect($user, $client, currentTenant());

    $token = Token::query()->forceCreate([
        'id' => Str::random(80),
        'user_id' => $user->getKey(),
        'client_id' => $client->getKey(),
        'scopes' => ['mcp:use'],
        'revoked' => false,
        'expires_at' => now()->addHour(),
    ]);

    app(AuthorizeAiConnectionAction::class)->handle(
        new AuthorizeAiConnectionData((string) $client->getKey(), (int) $second->getKey(), AiAccessLevel::Read),
        $user,
    );

    expect($token->fresh()->revoked)->toBeTrue()
        ->and(AiConnection::query()->count())->toBe(1)
        ->and(AiConnection::query()->firstOrFail()->tenant_id)->toBe($second->getKey());
});

// ══════════════ مراجعة: الطرق الجانبية حوالين الموافقة ══════════════

it('الموافقة بتشتغل من غير سياق مؤسسة — الصلاحية بتتسأل على فريق المؤسسة المختارة', function (): void {
    $editor = userWithRole('editor');

    // شاشة الموافقة برا اللوحة: الفريق الحالي مركزي، مش فريق المؤسسة
    app(TenantContext::class)->set(null);

    expect($editor->can('connect', [AiConnection::class, currentTenant()]))->toBeTrue();
});

it('مفيش مسار موافقة لـ Passport — الموافقة الوحيدة بتسأل connect', function (): void {
    $this->actingAs(userWithRole('admin'))
        ->post('/oauth/authorize', ['state' => 'x', 'client_id' => oauthClient()->getKey(), 'auth_token' => 'x'])
        ->assertStatus(405);
});

it('مفيش موافقة تلقائية حتى لو فيه توكن شغّال لنفس العميل', function (): void {
    $user = userWithRole('admin');
    $client = oauthClient();
    connect($user, $client, currentTenant());

    Token::query()->forceCreate([
        'id' => Str::random(80),
        'user_id' => $user->getKey(),
        'client_id' => $client->getKey(),
        'scopes' => ['mcp:use'],
        'revoked' => false,
        'expires_at' => now()->addHour(),
    ]);

    $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => CLAUDE_CALLBACK,
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => 's',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', Str::random(64), true)), '+/', '-_'), '='),
        'code_challenge_method' => 'S256',
    ]))->assertOk()->assertSee(__('ai::ai.consent.approve'));
});

it('شيل access.ai من الدور بيفصل الروابط القائمة فوراً', function (): void {
    $user = userWithPermissions(['access.panel.admin', 'access.ai', 'view_any.announcements']);
    $client = oauthClient();
    connect($user, $client, currentTenant());

    mcpHttp($user, $client)->assertOk();

    Role::findByName('test_role', config('authorization.guard'))->revokePermissionTo('access.ai');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    mcpHttp($user->fresh(), $client)->assertForbidden();
});
