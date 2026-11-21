<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;
use Src\Contexts\Notifications\Application\Services\NotificationChannelResolver;
use Src\Contexts\Settings\Application\Actions\UpdateTenantSettingsAction;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Settings\Presentation\Filament\Clusters\TenantSettingsCluster;
use Src\Contexts\Settings\Presentation\Filament\Pages\ManageTenantAi;
use Src\Contexts\Settings\Presentation\Filament\Pages\ManageTenantAppearance;
use Src\Contexts\Settings\Presentation\Filament\Pages\ManageTenantNotifications;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/*
|--------------------------------------------------------------------------
| إعدادات المؤسسة: مظهرها وإشعاراتها — والباقي عام (docs/24 بند ٦)
|--------------------------------------------------------------------------
*/

it('مدير المؤسسة بيوصل لإعداداتها بتبويبين', function (): void {
    actingAsRole('admin');

    $this->get(TenantSettingsCluster::getUrl())->assertRedirect(ManageTenantAppearance::getUrl());

    $this->get(ManageTenantAppearance::getUrl())
        ->assertOk()
        ->assertSee(ManageTenantNotifications::getUrl(), escape: false)
        ->assertSee(ManageTenantAi::getUrl(), escape: false);
});

it('المشاهد مالوش إعدادات المؤسسة', function (): void {
    actingAsRole('viewer');

    $this->get(TenantSettingsCluster::getUrl())->assertForbidden();
});

it('الحفظ بيكتب للمؤسسة دي بس والتانية بتفضل على العام', function (): void {
    $other = Tenant::factory()->create();
    actingAsRole('admin');

    Livewire::test(ManageTenantAppearance::class)
        ->assertSet('data.allow_theme_switch', true)
        ->fillForm(['font_family' => 'Cairo', 'allow_theme_switch' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(TenantSettings::class);

    expect($settings->get('appearance', 'font_family'))->toBe('Cairo')
        ->and($settings->get('appearance', 'allow_theme_switch'))->toBeFalse();

    app(TenantContext::class)->forTenant($other->getKey(), function () use ($settings): void {
        expect($settings->get('appearance', 'font_family'))->not->toBe('Cairo');
    });
});

it('قناة مقفولة للمؤسسة بتتشال من الإشعارات بتاعتها بس', function (): void {
    $other = Tenant::factory()->create();
    $admin = actingAsRole('admin');

    Livewire::test(ManageTenantNotifications::class)
        ->fillForm(['database_enabled' => true, 'mail_enabled' => false, 'broadcast_enabled' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    $key = (string) array_key_first((array) config('notifications.catalog'));
    $resolver = app(NotificationChannelResolver::class);

    expect($resolver->for($admin, $key))->not->toContain('mail');

    app(TenantContext::class)->forTenant($other->getKey(), function () use ($resolver, $admin, $key): void {
        $defaults = (array) config("notifications.catalog.{$key}.default");

        if (in_array('mail', $defaults, true)) {
            expect($resolver->for($admin, $key))->toContain('mail');
        }
    });
});

it('المجموعات العامة مابتتكتبش للمؤسسة حتى من المدير العام', function (string $group): void {
    actingAsRole('super_admin');

    expect(fn () => app(UpdateTenantSettingsAction::class)->handle($group, ['driver' => 'smtp'], auth()->user()))
        ->toThrow(AuthorizationException::class, __('authorization.denied.settings.global_group'));
})->with(['mail', 'storage', 'security', 'general']);

it('مفتاح الذكاء الاصطناعي بيتحفظ للمؤسسة دي بس', function (): void {
    $other = Tenant::factory()->create();
    actingAsRole('admin');

    Livewire::test(ManageTenantAi::class)
        ->assertSet('data.mcp_enabled', true)
        ->fillForm(['mcp_enabled' => false, 'allow_writes' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(TenantSettings::class);

    expect($settings->get('ai', 'mcp_enabled'))->toBeFalse()
        ->and($settings->get('ai', 'allow_writes'))->toBeFalse();

    app(TenantContext::class)->forTenant($other->getKey(), function () use ($settings): void {
        expect($settings->get('ai', 'mcp_enabled', true))->toBeTrue();
    });
});

it('المحرر مالوش تبويب الذكاء الاصطناعي', function (): void {
    actingAsRole('editor');

    $this->get(ManageTenantAi::getUrl())->assertForbidden();
});
