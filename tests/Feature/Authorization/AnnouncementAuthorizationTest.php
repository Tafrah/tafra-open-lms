<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Content\Application\Actions\PublishAnnouncementAction;
use Src\Contexts\Content\Application\DataObjects\PublishAnnouncementData;
use Src\Contexts\Content\Domain\Exceptions\AnnouncementAlreadyPublishedException;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

dataset('announcement_roles_and_abilities', [
    ['super_admin', ['viewAny' => true, 'create' => true, 'update' => true, 'delete' => true, 'publish' => true]],
    ['admin', ['viewAny' => true, 'create' => true, 'update' => true, 'delete' => true, 'publish' => true]],
    ['editor', ['viewAny' => true, 'create' => true, 'update' => true, 'delete' => false, 'publish' => false]],
    ['viewer', ['viewAny' => true, 'create' => false, 'update' => false, 'delete' => false, 'publish' => false]],
]);

it('يطبّق مصفوفة صلاحيات الإعلانات', function (string $role, array $expected): void {
    $user = userWithRole($role);
    $record = Announcement::factory()->draft()->create();

    foreach ($expected as $ability => $allowed) {
        $argument = in_array($ability, ['viewAny', 'create'], true) ? Announcement::class : $record;

        expect($user->can($ability, $argument))
            ->toBe($allowed, "الدور {$role} — القدرة {$ability}");
    }
})->with('announcement_roles_and_abilities');

it('يمنع نشر إعلان منشور بالفعل ويوضّح السبب', function (): void {
    $user = userWithRole('admin');
    $announcement = Announcement::factory()->published()->create();

    $response = Gate::forUser($user)->inspect('publish', $announcement);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(__('authorization.denied.announcement.not_draft'));
});

it('يمنع نشر إعلان ناقص العنوان الاحتياطي', function (): void {
    $user = userWithRole('admin');
    $announcement = Announcement::factory()->draft()->create(['title' => ['ar' => 'عنوان']]);

    $response = Gate::forUser($user)->inspect('publish', $announcement);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toContain(__('common.locales.en'));
});

it('يمنع نشر إعلان انتهى تاريخه', function (): void {
    $user = userWithRole('admin');
    $announcement = Announcement::factory()->draft()->expired()->create();

    $response = Gate::forUser($user)->inspect('publish', $announcement);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(__('authorization.denied.announcement.already_expired'));
});

it('يمنع حذف إعلان منشور — يتأرشف مش يتحذف', function (): void {
    $user = userWithRole('admin');
    $announcement = Announcement::factory()->published()->create();

    $response = Gate::forUser($user)->inspect('delete', $announcement);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(__('authorization.denied.announcement.published_must_archive'));
});

it('يمنع تثبيت إعلان غير منشور', function (): void {
    $user = userWithRole('admin');
    $announcement = Announcement::factory()->draft()->create();

    $response = Gate::forUser($user)->inspect('pin', $announcement);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(__('authorization.denied.announcement.pin_requires_published'));
});

it('يمنع تجاوز الحد الأقصى للإعلانات المثبّتة', function (): void {
    config(['content.max_pinned' => 2]);

    $user = userWithRole('admin');
    Announcement::factory()->count(2)->published()->pinned()->create();
    $candidate = Announcement::factory()->published()->create();

    $response = Gate::forUser($user)->inspect('pin', $candidate);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toContain('2');
});

it('لا يتجاوز المدير العام قواعد سلامة الإعلانات', function (): void {
    $super = userWithRole('super_admin');
    $published = Announcement::factory()->published()->create();

    // معاه كل الصلاحيات...
    expect($super->can('viewAny', Announcement::class))->toBeTrue();

    // ...ومع ذلك القواعد بتمنعه
    expect($super->can('publish', $published))->toBeFalse()
        ->and($super->can('delete', $published))->toBeFalse();
});

it('يخفي إعلان مؤسسة أخرى بـ 404 لا 403', function (): void {
    $a = currentTenant();
    $b = Tenant::factory()->create();

    $user = userWithRole('admin', $a);

    app(TenantContext::class)->set($b->getKey());
    $foreign = Announcement::factory()->create();

    app(TenantContext::class)->set($a->getKey());

    expect(Gate::forUser($user)->inspect('view', $foreign)->status())->toBe(404);
});

it('يرفض نشر إعلان منشور على مستوى الـ Action كمان', function (): void {
    userWithRole('admin');
    $announcement = Announcement::factory()->published()->create();

    expect(fn () => app(PublishAnnouncementAction::class)
        ->handle(new PublishAnnouncementData((int) $announcement->getKey())))
        ->toThrow(AnnouncementAlreadyPublishedException::class);
});
