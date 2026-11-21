<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Media\Domain\Models\MediaItem;
use Src\Contexts\Media\Presentation\Filament\Resources\MediaResource;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/*
|--------------------------------------------------------------------------
| عزل الملفات — الطبقة اللي مش في قاعدة البيانات (docs/04 بند ١٠)
|--------------------------------------------------------------------------
| المسار ده هو اللي بيمشي فيه رفع Filament: `addMedia()` مباشرة من غير
| الـ trait بتاعنا. لو `tenant_id` مااتحقنش هنا، الملف بيتخزّن تحت
| `tenants/global/` وبيختفي من شاشة الوسائط.
*/

beforeEach(function (): void {
    Storage::fake('public');
});

it('يحقن tenant_id حتى لو الرفع مش عبر الـ trait', function (): void {
    $user = actingAsRole('admin');

    $media = $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))
        ->toMediaCollection('avatar');

    expect($media->getCustomProperty('tenant_id'))->toBe(currentTenant()->getKey());
});

it('يخزّن الملف تحت مسار المؤسسة', function (): void {
    $user = actingAsRole('admin');

    $media = $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))
        ->toMediaCollection('avatar');

    expect($media->getPathRelativeToRoot())
        ->toStartWith('tenants/'.currentTenant()->getKey().'/');
});

it('لا يعرض ملف مؤسسة في شاشة وسائط مؤسسة تانية', function (): void {
    $a = currentTenant();
    $b = Tenant::factory()->create();

    $userA = actingAsRole('admin', $a);
    $userA->addMedia(UploadedFile::fake()->image('a.jpg'))->toMediaCollection('avatar');

    expect(MediaResource::getEloquentQuery()->count())->toBe(1);

    // نبدّل للمؤسسة التانية
    $userB = User::factory()->create();
    $userB->tenants()->attach($b->getKey(), ['joined_at' => now()]);
    $this->actingAs($userB);
    Filament::setTenant($b, isQuiet: true);
    app(TenantContext::class)->set($b->getKey());

    expect(MediaResource::getEloquentQuery()->count())->toBe(0);
});

it('يخفي ملف مؤسسة تانية بـ 404 لا 403', function (): void {
    $a = currentTenant();
    $b = Tenant::factory()->create();

    $userA = actingAsRole('admin', $a);
    $media = $userA->addMedia(UploadedFile::fake()->image('a.jpg'))->toMediaCollection('avatar');

    $userB = userWithRole('admin', $b);
    app(TenantContext::class)->set($b->getKey());

    $response = Gate::forUser($userB)->inspect('view', MediaItem::query()->find($media->getKey()));

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404);
});

it('يسيب الملفات العامة (بدون مؤسسة) متاحة', function (): void {
    // أصول العلامة بتترفع من صفحة الإعدادات العامة — مالهاش مؤسسة
    app(TenantContext::class)->set(null);

    $media = new MediaItem;

    expect($media->tenantId())->toBeNull();
});
