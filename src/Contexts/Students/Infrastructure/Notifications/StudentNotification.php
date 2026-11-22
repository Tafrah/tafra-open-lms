<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Infrastructure\Notifications;

use Src\Contexts\Notifications\Infrastructure\Notifications\BaseNotification;

/**
 * أساس إشعارات الطالب على الموقع (الجرس في منطقة الطالب).
 *
 * - قناة `database` بس: الطالب مالوش تفضيلات إشعارات (`notification_preferences`
 *   مربوطة بمستخدمي اللوحة — رقم الطالب ممكن يطابق رقم مستخدم).
 * - النص مش متخزّن مترجم: بنخزّن «النوع» والقيم، والموقع بيترجمها بلغة الطالب
 *   وقت العرض (`StudentNotificationPresenter`).
 * - بيتبعت بـ `notifyNow()`: الطالب موديل تابع لمؤسسة، والطابور مالوش سياق مؤسسة.
 */
abstract class StudentNotification extends BaseNotification
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    abstract protected function kind(): string;

    /** @return array<string, string|int|float|null> */
    abstract protected function params(): array;

    /** @return array{0: string, 1: array<string, int|string>} اسم الـ route ومعاملاته */
    abstract protected function link(): array;

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        [$route, $parameters] = $this->link();

        return [
            'format' => 'student',
            'kind' => $this->kind(),
            'params' => $this->params(),
            'route' => $route,
            'route_params' => $parameters,
        ];
    }
}
