<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Mcp;

use Src\Contexts\Academy\Presentation\Filament\Resources\EducationalLevelResource;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpResource;
use Src\Support\Presentation\Mcp\RecordOperation;

/**
 * الصفوف الدراسية للذكاء الاصطناعي — قراءة وإضافة وتعديل.
 */
final class EducationalLevelMcp extends McpResource
{
    private const LABELS = 'academy::academy.level.fields.';

    public static function filamentResource(): string
    {
        return EducationalLevelResource::class;
    }

    public function fields(): array
    {
        return [
            McpField::string('name')->required()->max(120)->label(self::LABELS.'name'),
            McpField::string('code')->required()->max(32)->label(self::LABELS.'code'),
            McpField::integer('order')->label(self::LABELS.'order'),
        ];
    }

    public function operations(): array
    {
        return [RecordOperation::List, RecordOperation::Show, RecordOperation::Create, RecordOperation::Update];
    }

    public function with(): array
    {
        return [];
    }
}
