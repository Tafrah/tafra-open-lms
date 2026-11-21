<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Mcp;

use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Exams\Application\Actions\PublishExamAction;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpRecordAction;
use Src\Support\Presentation\Mcp\McpResource;
use Src\Support\Presentation\Mcp\RecordOperation;

/**
 * الامتحانات — قراءة + نشر. الأسئلة والتصحيح من اللوحة.
 */
final class OnlineExamMcp extends McpResource
{
    private const LABELS = 'exams::exams.exam.fields.';

    public static function filamentResource(): string
    {
        return OnlineExamResource::class;
    }

    public function fields(): array
    {
        return [
            McpField::string('name')->readOnly()->label(self::LABELS.'name'),
            McpField::integer('questions_number')->readOnly()->label(self::LABELS.'questions_count'),
            McpField::integer('allowed_chances')->readOnly()->label(self::LABELS.'allowed_chances'),
            McpField::number('min_percentage')->readOnly()->label(self::LABELS.'min_percentage'),
            McpField::boolean('published')->readOnly()->filterable()->label(self::LABELS.'published'),
        ];
    }

    public function operations(): array
    {
        return [RecordOperation::List, RecordOperation::Show];
    }

    public function actions(): array
    {
        return [
            McpRecordAction::make('publish')
                ->label('exams::exams.exam.actions.publish')
                ->description('exams::exams.mcp.actions.publish')
                ->using(fn (Model $record, array $input, User $actor): OnlineExam => app(PublishExamAction::class)->handle($this->exam($record), $actor)),
        ];
    }

    public function with(): array
    {
        return [];
    }

    private function exam(Model $record): OnlineExam
    {
        throw_unless($record instanceof OnlineExam, \LogicException::class);

        return $record;
    }
}
