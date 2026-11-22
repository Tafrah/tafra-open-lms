<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Mcp;

use Src\Contexts\Academy\Domain\Enums\LessonType;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpResource;
use Src\Support\Presentation\Mcp\RecordOperation;

/**
 * مكتبة الدروس — قراءة (الرفع والتعديل من اللوحة).
 */
final class OnlineLessonMcp extends McpResource
{
    private const LABELS = 'academy::academy.lesson.fields.';

    public static function filamentResource(): string
    {
        return OnlineLessonResource::class;
    }

    public function fields(): array
    {
        return [
            McpField::string('name')->required()->max(255)->label(self::LABELS.'name'),
            McpField::enum('type', LessonType::class)->required()->filterable()->label(self::LABELS.'type'),
            McpField::string('external_url')->max(2048)->label(self::LABELS.'youtube_url'),
            McpField::text('description')->label(self::LABELS.'description'),
            McpField::boolean('allow_download')->label(self::LABELS.'allow_download'),
        ];
    }

    public function operations(): array
    {
        return [RecordOperation::List, RecordOperation::Show];
    }

    public function with(): array
    {
        return [];
    }
}
