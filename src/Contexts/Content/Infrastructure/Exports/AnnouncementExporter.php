<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Infrastructure\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Notifications\Infrastructure\Exports\BaseExporter;

final class AnnouncementExporter extends BaseExporter
{
    protected static ?string $model = Announcement::class;

    public static function resourceLabel(): string
    {
        return __('content::content.announcement.plural');
    }

    /** @return array<int, ExportColumn> */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('title')->label(__('content::content.announcement.fields.title')),
            ExportColumn::make('status')->label(__('common.status')),
            ExportColumn::make('severity')->label(__('content::content.announcement.fields.severity')),
            ExportColumn::make('author.name')->label(__('content::content.announcement.fields.author')),
            ExportColumn::make('published_at')->label(__('content::content.announcement.fields.published_at')),
            ExportColumn::make('created_at')->label(__('common.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return trans_choice('content::content.announcement.export.completed', $export->successful_rows, [
            'count' => fc_number($export->successful_rows),
        ]);
    }
}
