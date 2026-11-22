<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Support;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;

/**
 * إشعار الطالب المتخزّن (نوع + قيم) ← عنوان ونص ورابط بلغة الطالب دلوقتي.
 * أي نوع مش معروف بيتعرض كإشعار عام بدل ما الصفحة تقع.
 */
final class StudentNotificationPresenter
{
    private const KINDS = [
        'invoice_approved' => ['icon' => 'check-circle', 'tone' => 'success'],
        'invoice_rejected' => ['icon' => 'x-circle', 'tone' => 'danger'],
        'attempt_graded' => ['icon' => 'exam', 'tone' => 'primary'],
    ];

    /** @return array{title: string, body: string, url: ?string, icon: string, tone: string} */
    public function present(DatabaseNotification $notification): array
    {
        $data = (array) $notification->data;
        $kind = (string) ($data['kind'] ?? '');
        $meta = self::KINDS[$kind] ?? ['icon' => 'bell', 'tone' => 'primary'];
        $key = isset(self::KINDS[$kind]) ? $kind : 'generic';

        $params = collect((array) ($data['params'] ?? []))
            ->map(fn (mixed $value, string $name): string => match (true) {
                $name === 'percentage' && is_numeric($value) => fc_number((float) $value, 1),
                default => (string) $value,
            })
            ->all();

        $body = __("storefront::storefront.notifications.kinds.{$key}.body", $params);

        if ($kind === 'invoice_rejected' && filled($params['reason'] ?? null)) {
            $body .= ' '.__('storefront::storefront.notifications.reason', ['reason' => $params['reason']]);
        }

        return [
            'title' => __("storefront::storefront.notifications.kinds.{$key}.title", $params),
            'body' => $body,
            'url' => $this->url($data),
            'icon' => $meta['icon'],
            'tone' => $meta['tone'],
        ];
    }

    /** @param array<string, mixed> $data */
    private function url(array $data): ?string
    {
        $route = $data['route'] ?? null;

        // بس مسارات الموقع — مفيش رابط برّه منطقة الطالب من بيانات متخزّنة
        if (! is_string($route) || ! str_starts_with($route, 'site.') || ! Route::has($route)) {
            return null;
        }

        try {
            return route($route, (array) ($data['route_params'] ?? []));
        } catch (\Throwable) {
            return null;
        }
    }
}
