<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Src\Contexts\Notifications\Infrastructure\Notifications\BaseNotification;

/** دعوة مستخدم جديد للمؤسسة (docs/01 · docs/09 بند ٢). */
final class UserInvitedNotification extends BaseNotification
{
    public function __construct(
        public readonly string $inviteUrl,
        public readonly string $organization,
    ) {
        parent::__construct();
    }

    public static function key(): string
    {
        return 'user_invited';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('identity::identity.user.notifications.invited.subject'))
            ->greeting(__('common.greeting', ['name' => $notifiable->name]))
            ->line(__('identity::identity.user.notifications.invited.body', ['organization' => $this->organization]))
            ->action(__('identity::identity.user.notifications.invited.cta'), $this->inviteUrl)
            ->line(__('identity::identity.user.notifications.invited.expiry'))
            ->salutation(__('common.salutation'));
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('identity::identity.user.notifications.invited.subject'))
            ->body(__('identity::identity.user.notifications.invited.short', ['organization' => $this->organization]))
            ->icon('heroicon-o-user-plus')
            ->iconColor('success')
            ->actions([
                Action::make('accept')
                    ->label(__('identity::identity.user.notifications.invited.cta'))
                    ->url($this->inviteUrl)
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
