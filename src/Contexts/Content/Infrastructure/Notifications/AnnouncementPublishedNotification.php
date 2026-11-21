<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Infrastructure\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Notifications\Infrastructure\Notifications\BaseNotification;

final class AnnouncementPublishedNotification extends BaseNotification
{
    public function __construct(
        public readonly int $announcementId,
        public readonly string $title,
        public readonly string $severity,
    ) {
        parent::__construct();
    }

    public static function key(): string
    {
        return 'announcement_published';
    }

    public static function fromAnnouncement(Announcement $announcement): self
    {
        return new self(
            announcementId: (int) $announcement->getKey(),
            title: (string) $announcement->getTranslation('title', config('app.fallback_locale'), true),
            severity: $announcement->severity->value,
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('content::content.announcement.notifications.mail_subject'))
            ->greeting(__('common.greeting', ['name' => $notifiable->name]))
            ->line($this->title)
            ->line(__('content::content.announcement.notifications.mail_body'))
            ->salutation(__('common.salutation'));
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('content::content.announcement.notifications.published'))
            ->body($this->title)
            ->icon('heroicon-o-megaphone')
            ->iconColor(match ($this->severity) {
                'urgent' => 'danger',
                'warning' => 'warning',
                default => 'info',
            })
            ->actions([
                Action::make('view')
                    ->label(__('common.view'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
