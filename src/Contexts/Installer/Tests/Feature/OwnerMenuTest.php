<?php

declare(strict_types=1);

use Src\Support\Infrastructure\Authorization\CentralRoles;

/*
| «نسيت كلمة السر» محتاج بريد، وإعدادات البريد في اللوحة المركزية — صاحب
| المنصة لازم يلاقي ليها رابط من لوحة التحكم.
*/

it('صاحب المنصة بيشوف رابط إعدادات البريد والمدير العادي لأ', function (): void {
    $label = __('settings::settings.menu.platform');
    $user = actingAsRole('admin');
    $home = '/admin/t/'.currentTenant()->slug;

    $this->get($home)->assertOk()->assertDontSee($label);

    app(CentralRoles::class)->assign($user, (string) config('authorization.super_admin_role'));

    $this->get($home)->assertOk()->assertSee($label);
});
