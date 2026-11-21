<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Presentation\Mcp;

use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Audit\Presentation\Filament\Resources\ActivityLogResource;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpResource;

/**
 * سجل النشاط — قراءة بس: «مين عمل إيه وإمتى» سؤال مفيد للذكاء الاصطناعي،
 * والسجل نفسه للإضافة بس. القيم القديمة والجديدة مابترجعش — بتفضل في اللوحة.
 */
class ActivityLogMcp extends McpResource
{
    private const LABELS = 'audit::audit.log.fields.';

    public static function filamentResource(): string
    {
        return ActivityLogResource::class;
    }

    public function fields(): array
    {
        return [
            McpField::string('log_name')->readOnly()->filterable()->label(self::LABELS.'channel'),
            McpField::string('event')->readOnly()->filterable()->label(self::LABELS.'description'),
            McpField::string('description')->readOnly()->label(self::LABELS.'description'),
            McpField::string('subject_type')->readOnly()->label(self::LABELS.'subject'),
            McpField::string('subject_id')->readOnly()->label(self::LABELS.'subject'),
        ];
    }

    public function omitted(): array
    {
        return [
            'create' => 'mcp.omitted.audit_append_only',
            'update' => 'mcp.omitted.audit_append_only',
            'delete' => 'mcp.omitted.audit_append_only',
            'prune' => 'mcp.omitted.prune',
        ];
    }

    public function searchable(): array
    {
        return ['description'];
    }

    public function extra(Model $record): array
    {
        $causer = $record->getRelationValue('causer');

        return [
            'causer' => $causer?->getAttribute('name'),
            'subject_type' => class_basename((string) $record->getAttribute('subject_type')),
        ];
    }
}
