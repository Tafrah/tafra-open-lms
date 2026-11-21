<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Src\Contexts\Notifications\Infrastructure\Notifications\BaseNotification;

/** تنبيه أمني عند أول دخول من جهاز جديد (docs/12 بند ٢). */
final class NewDeviceLoginNotification extends BaseNotification
{
    public function __construct(
        public readonly string $deviceName,
        public readonly string $ipAddress,
    ) {
        parent::__construct();
    }

    public static function key(): string
    {
        return 'new_device_login';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('identity::identity.device.notifications.new_device_subject'))
            ->greeting(__('common.greeting', ['name' => $notifiable->name]))
            ->line(__('identity::identity.device.notifications.new_device_body', [
                'device' => $this->deviceName,
                'ip' => $this->ipAddress,
            ]))
            ->line(__('identity::identity.device.notifications.new_device_action'))
            ->salutation(__('common.salutation'));
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('identity::identity.device.notifications.new_device_subject'))
            ->body(__('identity::identity.device.notifications.new_device_body', [
                'device' => $this->deviceName,
                'ip' => $this->ipAddress,
            ]))
            ->icon('heroicon-o-device-phone-mobile')
            ->iconColor('warning')
            ->getDatabaseMessage();
    }
}
