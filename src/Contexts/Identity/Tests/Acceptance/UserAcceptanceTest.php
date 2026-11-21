<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Src\Contexts\Identity\Application\Actions\InviteUserAction;
use Src\Contexts\Identity\Application\DataObjects\InviteUserData;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Domain\Events\UserInvited;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Infrastructure\Listeners\SendUserInvitation;
use Src\Contexts\Identity\Infrastructure\Notifications\UserInvitedNotification;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource\Pages\CreateUser;

it('ينشئ المستخدم ويربطه بالمؤسسة ويطلق الحدث', function (): void {
    Event::fake([UserInvited::class]);

    actingAsRole('admin');

    $user = app(InviteUserAction::class)->handle(
        new InviteUserData(
            name: 'مستخدم مدعو',
            email: 'invited@futurecode.test',
            tenantId: (int) currentTenant()->getKey(),
            role: 'viewer',
        ),
    );

    expect($user->status)->toBe(UserStatus::Invited)
        ->and($user->code)->toStartWith('USR-')
        ->and($user->tenants()->whereKey(currentTenant()->getKey())->exists())->toBeTrue()
        ->and($user->getRoleNames()->all())->toContain('viewer');

    Event::assertDispatched(UserInvited::class);
});

it('يبعت الدعوة برابط صالح', function (): void {
    Notification::fake();

    actingAsRole('admin');

    $user = app(InviteUserAction::class)->handle(
        new InviteUserData(
            name: 'مدعو',
            email: 'invitee@futurecode.test',
            tenantId: (int) currentTenant()->getKey(),
            role: 'viewer',
        ),
    );

    Notification::assertSentTo($user, UserInvitedNotification::class,
        function (UserInvitedNotification $notification): bool {
            return str_contains($notification->inviteUrl, 'password-reset')
                && str_contains($notification->inviteUrl, 'token=');
        });
});

it('لا يبعت دعوة لو الحدث من غير رابط', function (): void {
    Notification::fake();

    $user = userWithRole('viewer');

    app(SendUserInvitation::class)->handle(
        new UserInvited(userId: (int) $user->getKey(), tenantId: (int) currentTenant()->getKey()),
    );

    Notification::assertNothingSent();
});

it('ينشئ المستخدم من الشاشة عبر الـ Action', function (): void {
    Notification::fake();

    actingAsRole('admin');

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'مستخدم من الشاشة',
            'email' => 'screen@futurecode.test',
            'password' => 'Str0ng!Passw0rd#2026',
            'status' => UserStatus::Invited->value,
            'is_active' => true,
            'roles' => ['viewer'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = User::query()->where('email', 'screen@futurecode.test')->first();

    expect($created)->not->toBeNull()
        ->and($created->tenants()->whereKey(currentTenant()->getKey())->exists())->toBeTrue()
        ->and($created->getRoleNames()->all())->toContain('viewer');

    Notification::assertSentTo($created, UserInvitedNotification::class);
});

it('يحفظ كلمة المرور اللي اتكتبت في الفورم من غير ما يعيد تشفيرها', function (): void {
    Notification::fake();

    actingAsRole('admin');

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'صاحب كلمة مرور',
            'email' => 'pw@futurecode.test',
            'password' => 'Str0ng!Passw0rd#2026',
            'roles' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = User::query()->where('email', 'pw@futurecode.test')->first();

    expect(Hash::check('Str0ng!Passw0rd#2026', $created->password))->toBeTrue();
});
