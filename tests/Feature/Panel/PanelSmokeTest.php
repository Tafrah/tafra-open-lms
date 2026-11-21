<?php

declare(strict_types=1);

use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource;

it('يفتح صفحة الدخول', function (): void {
    $this->get('/admin/login')->assertOk();
});

it('يفتح لوحة المعلومات لمستخدم مصرّح له', function (): void {
    $user = actingAsRole('admin');

    $this->get('/admin/t/'.currentTenant()->slug)->assertOk();
});

it('يفتح قائمة المستخدمين للمدير', function (): void {
    actingAsRole('admin');

    $this->get(UserResource::getUrl('index'))->assertOk();
});

it('يمنع المشاهد من صفحة إنشاء مستخدم', function (): void {
    actingAsRole('viewer');

    $this->get(UserResource::getUrl('create'))->assertForbidden();
});
