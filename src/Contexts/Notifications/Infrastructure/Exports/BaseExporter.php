<?php

declare(strict_types=1);

namespace Src\Contexts\Notifications\Infrastructure\Exports;

use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Notifications\Notification;
use Src\Contexts\Notifications\Infrastructure\Notifications\ExportReadyNotification;

/**
 * الأساس لكل مُصدِّرات التطبيق.
 *
 * Filament بيبعت إشعار اللوحة بنفسه (هو اللي فيه رابط التنزيل).
 * الكلاس ده بيوصّل نفس الخبر للقنوات اللي المستخدم اختارها كمان —
 * من غير كده كان مفتاح `export_ready` في شاشة التفضيلات وعد مش بنوفيه.
 */
abstract class BaseExporter extends Exporter
{
    /** اسم المورد المعروض في الإشعار */
    abstract public static function resourceLabel(): string;

    public static function modifyCompletedNotification(Notification $notification, Export $export): Notification
    {
        $export->user->notify(new ExportReadyNotification(
            resource: static::resourceLabel(),
            rows: (int) $export->successful_rows,
            downloadUrl: route('filament.exports.download', ['export' => $export, 'format' => 'csv']),
        ));

        return $notification;
    }

    public function getJobQueue(): ?string
    {
        return 'exports';
    }
}
