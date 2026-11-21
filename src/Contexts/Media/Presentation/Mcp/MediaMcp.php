<?php

declare(strict_types=1);

namespace Src\Contexts\Media\Presentation\Mcp;

use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Media\Presentation\Filament\Resources\MediaResource;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpResource;
use Src\Support\Presentation\Mcp\RecordOperation;

/**
 * مكتبة الوسائط — عرض وحذف. الرفع محتاج ملف حقيقي من الشخص، والتنزيل
 * بروابط موقّعة لمتصفح مسجّل — الاتنين من اللوحة.
 */
final class MediaMcp extends McpResource
{
    private const LABELS = 'media::media.item.fields.';

    public static function filamentResource(): string
    {
        return MediaResource::class;
    }

    public function fields(): array
    {
        return [
            McpField::string('name')->readOnly()->sortable()->label(self::LABELS.'name'),
            McpField::string('file_name')->readOnly()->label(self::LABELS.'name'),
            McpField::string('collection_name')->readOnly()->filterable()->label(self::LABELS.'collection'),
            McpField::string('mime_type')->readOnly()->filterable()->label(self::LABELS.'mime'),
            McpField::integer('size')->readOnly()->sortable()->label(self::LABELS.'size'),
        ];
    }

    public function operations(): array
    {
        return [RecordOperation::List, RecordOperation::Show, RecordOperation::Delete];
    }

    public function omitted(): array
    {
        return [
            'create' => 'mcp.omitted.upload',
            'download' => 'mcp.omitted.download',
            'changeDisk' => 'mcp.omitted.change_disk',
        ];
    }

    public function searchable(): array
    {
        return ['name', 'file_name'];
    }

    public function extra(Model $record): array
    {
        return ['attached_to' => class_basename((string) $record->getAttribute('model_type'))];
    }
}
