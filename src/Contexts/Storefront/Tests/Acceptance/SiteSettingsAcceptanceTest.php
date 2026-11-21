<?php

declare(strict_types=1);

require_once __DIR__.'/../storefront-helpers.php';

use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Settings\Domain\Models\TenantSetting;
use Src\Contexts\Settings\Presentation\Filament\Clusters\TenantSettingsCluster;
use Src\Contexts\Storefront\Application\Actions\UpdateSiteSettingsAction;
use Src\Contexts\Storefront\Presentation\Filament\Pages\ManageSiteSettings;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/*
|--------------------------------------------------------------------------
| «إعدادات الموقع» — تبويب في إعدادات المؤسسة (docs/product/plan.html#admin)
|--------------------------------------------------------------------------
*/

it('المدير بيلاقي «إعدادات الموقع» تبويب جوه إعدادات المؤسسة', function (): void {
    actingAsRole('admin');

    $this->get(ManageSiteSettings::getUrl())->assertOk()->assertSee(__('storefront::storefront.settings.page'));
    $this->get(TenantSettingsCluster::getUrl())->assertRedirect();

    expect(ManageSiteSettings::getUrl())->toContain('/organization-settings/site');
});

it('المحرر والمساعد والمشاهد مالهمش إعدادات الموقع', function (string $role): void {
    actingAsRole($role);

    $this->get(ManageSiteSettings::getUrl())->assertForbidden();
})->with(['editor', 'assistant', 'viewer']);

it('الحفظ بيكتب للمؤسسة دي والموقع بيعرض القيم الجديدة', function (): void {
    $other = Tenant::factory()->create();
    actingAsRole('admin');

    Livewire::test(ManageSiteSettings::class)
        ->assertSet('data.font_family', 'Almarai')
        ->assertSet('data.primary_color', config('storefront.defaults.primary_color'))
        ->fillForm([
            'platform_name' => 'Chem Academy',
            'teacher_name' => 'Mr. Ahmed',
            'subject' => 'Chemistry',
            'primary_color' => '#0E7490',
            'secondary_color' => '#334155',
            'font_family' => 'Cairo',
            'whatsapp_number' => '01011122233',
            'parent_phone_required' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(TenantSettings::class);
    expect($settings->get('site', 'platform_name'))->toBe('Chem Academy')
        ->and($settings->get('site', 'font_family'))->toBe('Cairo')
        ->and($settings->get('site', 'parent_phone_required'))->toBeTrue();

    app(TenantContext::class)->forTenant($other->getKey(), function () use ($settings): void {
        expect($settings->get('site', 'platform_name'))->toBeNull();
    });
});

it('اللون لازم يبقى hex صحيح', function (): void {
    actingAsRole('admin');

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['primary_color' => 'red; background:url(x)'])
        ->call('save')
        ->assertHasFormErrors(['primary_color']);
});

it('روابط السوشيال لازم تبقى روابط', function (): void {
    actingAsRole('admin');

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['facebook_url' => 'javascript:alert(1)'])
        ->call('save')
        ->assertHasFormErrors(['facebook_url']);
});

it('الأكشن نفسه بيرفض اللي مالوش access.site_settings (الإخفاء UX بس)', function (): void {
    $editor = userWithRole('editor');

    expect(fn () => app(UpdateSiteSettingsAction::class)->handle(['platform_name' => 'x'], $editor))
        ->toThrow(AuthorizationException::class);
});

it('مفاتيح زيادة جاية من الفورم مابتتكتبش', function (): void {
    $admin = userWithRole('admin');

    app(UpdateSiteSettingsAction::class)->handle(['platform_name' => 'Ok', 'evil_key' => 'x'], $admin);

    expect(app(TenantSettings::class)->get('site', 'platform_name'))->toBe('Ok')
        ->and(TenantSetting::query()->where('key', 'evil_key')->exists())->toBeFalse();
});

it('الإعدادات المحفوظة بتظهر على الصفحة الرئيسية', function (): void {
    $admin = userWithRole('admin');
    app(UpdateSiteSettingsAction::class)->handle(['platform_name' => 'Visible Academy', 'primary_color' => '#123456'], $admin);
    auth()->logout();

    $this->get('/')->assertOk()->assertSee('Visible Academy')->assertSee('--color-primary: #123456', escape: false);
});
