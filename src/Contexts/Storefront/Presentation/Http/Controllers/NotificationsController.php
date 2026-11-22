<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Src\Contexts\Storefront\Application\Queries\StudentAnnouncements;
use Src\Contexts\Storefront\Presentation\Http\Controllers\Concerns\ResolvesStudent;
use Src\Contexts\Storefront\Presentation\Support\StudentNotificationPresenter;

/**
 * «الإشعارات»: إشعارات الطالب (الإيصالات، التصحيح) + إعلانات المدرس.
 * فتح الصفحة = كل الإشعارات اتقرت (الجديد بيفضل متعلّم في نفس الفتحة بس).
 */
final class NotificationsController
{
    use ResolvesStudent;

    public function __invoke(StudentAnnouncements $announcements, StudentNotificationPresenter $presenter): View
    {
        $student = $this->student();

        $notifications = $student->notifications()->latest()->paginate(15);
        $unread = $notifications->getCollection()->whereNull('read_at')->pluck('id')->all();

        $student->unreadNotifications()->update(['read_at' => now()]);

        return view('storefront::pages.student.notifications', [
            'notifications' => $notifications,
            'items' => $notifications->getCollection()->map(fn ($notification): array => [
                ...$presenter->present($notification),
                'unread' => in_array($notification->id, $unread, true),
                'at' => $notification->created_at,
            ]),
            'announcements' => $announcements->latest(),
        ]);
    }
}
