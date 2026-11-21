<?php

declare(strict_types=1);

use FutureCode\Onboarding\Facades\Onboarding;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource;

/*
|--------------------------------------------------------------------------
| رحلة الأونبوردنج في متصفح حقيقي (docs/15 بند ١٠ · docs/17 بند ١٣)
|--------------------------------------------------------------------------
| اختبارات Livewire مابتشغّلش JavaScript — الجولة ممكن تبقى «متسجّلة»
| ومابتظهرش أبداً. الرحلة دي كشفت الاتنين فعلاً: الـ JS ماكانش بيتحمّل
| في اللوحة، والإغلاق ماكانش بيتسجّل (docs/21 بند ٣٠).
*/

it('الجولة بتبدأ لوحدها أول زيارة ومابترجعش بعد ما تتقفل', function (): void {
    actingAsRole('admin');

    $url = UserResource::getUrl('index');

    $page = visit($url);

    $page->assertVisible('.driver-popover')
        ->assertSee(__('identity::identity.tours.users.search.title'));

    assertUiQuality($page);

    $page->click('.driver-popover-close-btn');
    $page->wait(1);

    expect(Onboarding::progress()->fresh()->hasCompletedTour('users.list'))->toBeTrue();

    visit($url)->assertMissing('.driver-popover');
});

it('الجولة بتظهر بالإنجليزي باتجاه ltr', function (): void {
    $user = actingAsRole('admin');
    $user->forceFill(['locale' => 'en'])->save();

    $page = visit(UserResource::getUrl('index'));

    $page->assertVisible('.driver-popover')
        ->assertSee(__('identity::identity.tours.users.search.title', [], 'en'));

    assertUiQuality($page, 'en');
});
