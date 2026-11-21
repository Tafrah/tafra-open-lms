<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Src\Contexts\Content\Application\Actions\PublishAnnouncementAction;
use Src\Contexts\Content\Application\DataObjects\PublishAnnouncementData;
use Src\Contexts\Content\Domain\Enums\AnnouncementStatus;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Content\Infrastructure\Notifications\AnnouncementPublishedNotification;
use Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource\Pages\CreateAnnouncement;
use Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource\Pages\ListAnnouncements;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

it('ينشئ إعلاناً بالمؤسسة الصحيحة', function (): void {
    actingAsRole('admin');

    $announcement = Announcement::factory()->create();

    expect($announcement->tenant_id)->toBe(currentTenant()->getKey());
});

it('ينشئ إعلاناً من الشاشة', function (): void {
    actingAsRole('admin');

    Livewire::test(CreateAnnouncement::class)
        ->fillForm([
            'title' => ['ar' => 'إعلان تجريبي', 'en' => 'Test announcement'],
            'body' => ['ar' => '<p>النص</p>', 'en' => '<p>Body</p>'],
            'severity' => 'info',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('announcements', [
        'severity' => 'info',
        'tenant_id' => currentTenant()->getKey(),
    ]);
});

it('يتحقق من الحقول الإلزامية', function (): void {
    actingAsRole('admin');

    Livewire::test(CreateAnnouncement::class)
        ->fillForm([])
        ->call('create')
        ->assertHasFormErrors(['title.en' => 'required']);
});

it('يسند الكاتب برمجياً لا من الفورم', function (): void {
    $admin = actingAsRole('admin');

    Livewire::test(CreateAnnouncement::class)
        ->fillForm([
            'title' => ['ar' => 'عنوان', 'en' => 'Title'],
            'body' => ['ar' => '<p>نص</p>', 'en' => '<p>Body</p>'],
            'severity' => 'info',
        ])
        ->call('create');

    expect(Announcement::latest('id')->first()->author_id)->toBe($admin->getKey());
});

it('ينشر الإعلان ويحدّث حالته', function (): void {
    $admin = actingAsRole('admin');
    $announcement = Announcement::factory()->draft()->create();

    app(PublishAnnouncementAction::class)->handle(
        new PublishAnnouncementData((int) $announcement->getKey(), notifyUsers: false),
        $admin,
    );

    expect($announcement->fresh()->status)->toBe(AnnouncementStatus::Published)
        ->and($announcement->fresh()->published_at)->not->toBeNull();
});

it('يبعث إشعاراً للمستخدمين عند النشر', function (): void {
    Notification::fake();

    $admin = actingAsRole('admin');
    $others = User::factory()->count(3)->create();

    foreach ($others as $user) {
        $user->tenants()->attach(currentTenant()->getKey(), ['joined_at' => now()]);
    }

    $announcement = Announcement::factory()->draft()->create();

    app(PublishAnnouncementAction::class)->handle(
        new PublishAnnouncementData((int) $announcement->getKey(), notifyUsers: true),
    );

    Notification::assertSentTo($others, AnnouncementPublishedNotification::class);
});

it('يخفي إجراءات النشر والحذف عن المشاهد', function (): void {
    actingAsRole('viewer');
    $announcement = Announcement::factory()->draft()->create();

    Livewire::test(ListAnnouncements::class)
        ->assertTableActionHidden('publish', $announcement)
        ->assertTableActionHidden('delete', $announcement);
});

it('لا يسرّب إعلانات بين المؤسسات', function (): void {
    $a = currentTenant();
    $b = Tenant::factory()->create();

    $context = app(TenantContext::class);

    $context->set($a->getKey());
    Announcement::factory()->count(2)->create();

    $context->set($b->getKey());
    Announcement::factory()->count(5)->create();

    $context->set($a->getKey());

    expect(Announcement::count())->toBe(2);
});

it('ينشر الإعلانات المجدولة عبر الأمر', function (): void {
    actingAsRole('admin');

    Announcement::factory()->draft()->create(['published_at' => now()->subMinute()]);
    Announcement::factory()->draft()->create(['published_at' => now()->addDay()]);

    $this->artisan('announcements:publish-scheduled')->assertSuccessful();

    expect(Announcement::where('status', AnnouncementStatus::Published)->count())->toBe(1);
});
