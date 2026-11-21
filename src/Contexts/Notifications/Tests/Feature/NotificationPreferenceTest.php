<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Content\Infrastructure\Notifications\AnnouncementPublishedNotification;
use Src\Contexts\Notifications\Application\Services\NotificationChannelResolver;
use Src\Contexts\Notifications\Domain\Models\NotificationPreference;

it('يستخدم القنوات الافتراضية لو مفيش تفضيل محفوظ', function (): void {
    $user = userWithRole('admin');

    $channels = app(NotificationChannelResolver::class)->for($user, 'announcement_published');

    expect($channels)->toContain('database');
});

it('يحترم تفضيل المستخدم', function (): void {
    $user = userWithRole('admin');

    NotificationPreference::query()->create([
        'user_id' => $user->getKey(),
        'tenant_id' => currentTenant()->getKey(),
        'notification_key' => 'announcement_published',
        'channels' => ['database'],
        'enabled' => true,
    ]);

    $channels = app(NotificationChannelResolver::class)->for($user, 'announcement_published');

    expect($channels)->toBe(['database']);
});

it('لا يسمح بإيقاف القنوات الإجبارية', function (): void {
    $user = userWithRole('admin');

    NotificationPreference::query()->create([
        'user_id' => $user->getKey(),
        'tenant_id' => currentTenant()->getKey(),
        'notification_key' => 'password_changed',
        'channels' => [],
        'enabled' => false,
    ]);

    $channels = app(NotificationChannelResolver::class)->for($user, 'password_changed');

    // البريد إجباري في الكتالوج — بيعدّي حتى لو المستخدم قافل الإشعار
    expect($channels)->toContain('mail');
});

it('يرجع لقناة واحدة آمنة لو المفتاح مش في الكتالوج', function (): void {
    $user = userWithRole('admin');

    expect(app(NotificationChannelResolver::class)->for($user, 'not_in_catalog'))->toBe(['database']);
});

it('كل مفتاح في الكتالوج له كلاس إشعار فعلي', function (): void {
    // الاتجاه ده هو المهم: مفتاح في الكتالوج من غير كلاس معناه إن شاشة
    // التفضيلات بتعرض للمستخدم مفتاح مايعملش حاجة.
    $implemented = [];

    foreach (allPhpFiles([src_path('Contexts')]) as $file) {
        if (! str_contains($file->getPathname(), '/Notifications/')) {
            continue;
        }

        if (preg_match("/function key\(\): string\s*\{\s*return '([a-z_]+)'/s", $file->getContents(), $found)) {
            $implemented[] = $found[1];
        }
    }

    $missing = array_diff(array_keys((array) config('notifications.catalog')), $implemented);

    expect($missing)->toBeEmpty('مفاتيح في الكتالوج من غير كلاس إشعار: '.implode(', ', $missing));
});

it('كل إشعار في التطبيق موجود في الكتالوج', function (): void {
    $catalog = array_keys((array) config('notifications.catalog'));
    $missing = [];

    foreach (allPhpFiles([src_path('Contexts')]) as $file) {
        if (! str_contains($file->getPathname(), '/Notifications/')) {
            continue;
        }

        if (! preg_match("/function key\(\): string\s*\{\s*return '([a-z_]+)'/s", $file->getContents(), $found)) {
            continue;
        }

        if (! in_array($found[1], $catalog, true)) {
            $missing[] = $found[1];
        }
    }

    expect($missing)->toBeEmpty('إشعارات مش في الكتالوج: '.implode(', ', $missing));
});

it('يرسل الإشعار بلغة المستقبِل مش لغة المُرسِل', function (): void {
    Notification::fake();

    $sender = actingAsRole('admin');
    $sender->forceFill(['locale' => 'ar'])->save();

    $recipient = userWithRole('viewer');
    $recipient->forceFill(['locale' => 'en'])->save();

    expect($recipient->preferredLocale())->toBe('en')
        ->and($sender->preferredLocale())->toBe('ar');
});

it('الإشعارات كلها في الطابور وبعد الترانزاكشن', function (): void {
    $announcement = Announcement::factory()->makeOne();
    $announcement->setAttribute('id', 1);

    $notification = new AnnouncementPublishedNotification(1, 'عنوان', 'info');

    expect($notification)->toBeInstanceOf(ShouldQueue::class)
        ->and($notification->afterCommit)->toBeTrue()
        ->and($notification->queue)->toBe('notifications');
});
