<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Notifications\Domain\Models\NotificationPreference;
use Src\Contexts\Notifications\Presentation\Filament\Pages\NotificationPreferences;

/*
|--------------------------------------------------------------------------
| معايير قبول تفضيلات الإشعارات (docs/10 بند ٤)
|--------------------------------------------------------------------------
*/

/** @return list<string> */
function savedChannels(string $key): array
{
    return NotificationPreference::query()
        ->where('user_id', auth()->id())
        ->where('tenant_id', currentTenant()->getKey())
        ->where('notification_key', $key)
        ->firstOrFail()
        ->channels;
}

it('كل الأدوار بتفتح تفضيلاتها', function (string $role): void {
    actingAsRole($role);

    $this->get(NotificationPreferences::getUrl())->assertOk();
})->with(['admin', 'editor', 'viewer']);

it('الحفظ بيكتب القنوات المختارة للمستخدم في المؤسسة الحالية', function (): void {
    actingAsRole('viewer');

    Livewire::test(NotificationPreferences::class)
        ->set('state.announcement_published.mail', true)
        ->set('state.announcement_published.broadcast', false)
        ->call('save');

    expect(savedChannels('announcement_published'))->toBe(['database', 'mail']);
});

it('القناة الإجبارية بتتحفظ حتى لو اتشالت من الواجهة', function (): void {
    actingAsRole('viewer');

    Livewire::test(NotificationPreferences::class)
        ->set('state.password_changed.mail', false)
        ->set('state.password_changed.database', false)
        ->call('save');

    expect(savedChannels('password_changed'))->toBe(['mail']);
});
