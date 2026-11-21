<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Media\Domain\Models\MediaItem;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/*
|--------------------------------------------------------------------------
| مصفوفة الأدوار × القدرات — الوسائط
|--------------------------------------------------------------------------
| مفيش `area()` هنا — الوسائط متاحة لكل من معاه صلاحيتها.
| `viewer` بياخد `view_any.*` و`view.*` من النمط، فبيشوف من غير ما يعدّل.
*/

dataset('media_roles_and_abilities', [
    ['super_admin', ['viewAny' => true, 'create' => true, 'delete' => true, 'download' => true]],
    ['admin', ['viewAny' => true, 'create' => true, 'delete' => true, 'download' => true]],
    ['editor', ['viewAny' => true, 'create' => true, 'delete' => true, 'download' => true]],
    ['viewer', ['viewAny' => true, 'create' => false, 'delete' => false, 'download' => false]],
]);

/** جدول `media` polymorphic — لازم يتربط بموديل حقيقي. */
function mediaItemForCurrentTenant(): MediaItem
{
    $announcement = Announcement::factory()->create();

    return MediaItem::query()->create([
        'model_type' => $announcement::class,
        'model_id' => $announcement->getKey(),
        'name' => 'ملف تجريبي',
        'file_name' => 'test.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'public',
        'size' => 1024,
        'collection_name' => 'attachments',
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
    ]);
}

it('يطبّق الصلاحيات الصحيحة لكل دور', function (string $role, array $expected): void {
    $user = userWithRole($role);
    $media = mediaItemForCurrentTenant();

    foreach ($expected as $ability => $allowed) {
        $argument = in_array($ability, ['viewAny', 'create'], true) ? MediaItem::class : $media;

        expect($user->can($ability, $argument))
            ->toBe($allowed, "الدور {$role} — القدرة {$ability}");
    }
})->with('media_roles_and_abilities');

it('يخفي ملف مؤسسة تانية بـ 404 مش 403', function (): void {
    $tenant = currentTenant();
    $user = userWithPermissions(['view_any.media', 'view.media', 'download.media'], $tenant, 'media_viewer');

    $other = Tenant::factory()->create();
    app(TenantContext::class)->set($other->getKey());
    $foreign = mediaItemForCurrentTenant();

    app(TenantContext::class)->set($tenant->getKey());

    $response = Gate::forUser($user)->inspect('view', $foreign);

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404);
});
