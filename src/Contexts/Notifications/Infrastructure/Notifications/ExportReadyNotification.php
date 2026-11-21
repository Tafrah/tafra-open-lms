<?php

declare(strict_types=1);

namespace Src\Contexts\Notifications\Infrastructure\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * «ملف التصدير جاهز».
 *
 * ⚠️ الإشعار **داخل اللوحة** بيبعته Filament بنفسه (هو اللي بيحمل رابط
 * التنزيل، فمينفعش يتقفل). الكلاس ده بيغطّي القنوات **الزيادة** اللي
 * المستخدم اختارها — البريد أساساً — عشان تفضيلاته يكون ليها معنى.
 * راجع `BaseExporter`.
 */
final class ExportReadyNotification extends BaseNotification
{
    public function __construct(
        public readonly string $resource,
        public readonly int $rows,
        public readonly ?string $downloadUrl = null,
    ) {
        parent::__construct();
    }

    public static function key(): string
    {
        return 'export_ready';
    }

    /**
     * القناة `database` مستثناة — Filament بعتها بالفعل، ولو بعتناها
     * تاني المستخدم هيشوف الإشعار مرتين.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return array_values(array_diff(parent::via($notifiable), ['database']));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('notifications::notifications.export.subject'))
            ->greeting(__('common.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications::notifications.export.body', [
                'resource' => $this->resource,
                'rows' => fc_number($this->rows),
            ]));

        if ($this->downloadUrl !== null) {
            $message->action(__('common.download'), $this->downloadUrl);
        }

        return $message->line(__('notifications::notifications.export.expiry'))
            ->salutation(__('common.salutation'));
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('notifications::notifications.export.subject'),
            'body' => __('notifications::notifications.export.body', [
                'resource' => $this->resource,
                'rows' => fc_number($this->rows),
            ]),
        ];
    }
}
