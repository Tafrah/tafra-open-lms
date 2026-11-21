<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Src\Contexts\Notifications\Infrastructure\Notifications\BaseNotification;

/** تنبيه أمني إجباري عند تغيير كلمة المرور (docs/12 بند ٤). */
final class PasswordChangedNotification extends BaseNotification
{
    public static function key(): string
    {
        return 'password_changed';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('identity::identity.password.notifications.changed_subject'))
            ->greeting(__('common.greeting', ['name' => $notifiable->name]))
            ->line(__('identity::identity.password.notifications.changed_body'))
            ->line(__('identity::identity.password.notifications.changed_action'))
            ->salutation(__('common.salutation'));
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('identity::identity.password.notifications.changed_subject'))
            ->body(__('identity::identity.password.notifications.changed_body'))
            ->icon('heroicon-o-key')
            ->iconColor('warning')
            ->getDatabaseMessage();
    }
}
