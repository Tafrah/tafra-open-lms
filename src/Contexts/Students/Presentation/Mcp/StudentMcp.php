<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Presentation\Mcp;

use Src\Contexts\Students\Domain\Enums\StudentStatus;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpResource;
use Src\Support\Presentation\Mcp\RecordOperation;

/**
 * الطلبة — قراءة بس. الفلوس والاشتراك وكلمة السر من اللوحة.
 */
final class StudentMcp extends McpResource
{
    private const LABELS = 'students::students.student.fields.';

    public static function filamentResource(): string
    {
        return StudentResource::class;
    }

    public function fields(): array
    {
        return [
            McpField::string('name')->readOnly()->label(self::LABELS.'name'),
            McpField::string('phone')->readOnly()->label(self::LABELS.'phone'),
            McpField::string('code')->readOnly()->label(self::LABELS.'code'),
            McpField::number('balance')->readOnly()->label(self::LABELS.'balance'),
            McpField::enum('status', StudentStatus::class)->readOnly()->filterable()->label('common.status'),
        ];
    }

    public function operations(): array
    {
        return [RecordOperation::List, RecordOperation::Show];
    }

    public function omitted(): array
    {
        return [
            'adjustWallet' => 'students::students.mcp.omitted.money',
            'enroll' => 'students::students.mcp.omitted.access',
            'resetPassword' => 'students::students.mcp.omitted.account',
            'block' => 'students::students.mcp.omitted.account',
            'manageDevices' => 'students::students.mcp.omitted.account',
        ];
    }

    public function with(): array
    {
        return [];
    }
}
