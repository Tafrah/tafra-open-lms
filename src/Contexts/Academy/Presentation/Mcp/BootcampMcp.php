<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Mcp;

use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Academy\Application\Actions\PublishBootcampAction;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpRecordAction;
use Src\Support\Presentation\Mcp\McpResource;
use Src\Support\Presentation\Mcp\RecordOperation;

/**
 * الكورسات — قراءة + نشر بنفس الـ Action بتاع اللوحة. السعر بيتغيّر من اللوحة بس.
 */
final class BootcampMcp extends McpResource
{
    private const LABELS = 'academy::academy.bootcamp.fields.';

    public static function filamentResource(): string
    {
        return BootcampResource::class;
    }

    public function fields(): array
    {
        return [
            McpField::string('name')->readOnly()->label(self::LABELS.'name'),
            McpField::string('slug')->readOnly()->label(self::LABELS.'slug'),
            McpField::number('price')->readOnly()->label(self::LABELS.'price'),
            McpField::number('discount_price')->readOnly()->label(self::LABELS.'discount_price'),
            McpField::enum('publish_status', PublishStatus::class)->readOnly()->filterable()->label('common.status'),
            McpField::boolean('is_featured')->readOnly()->filterable()->label(self::LABELS.'is_featured'),
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
                ->label('academy::academy.bootcamp.actions.publish')
                ->description('academy::academy.mcp.actions.publish')
                ->using(fn (Model $record, array $input, User $actor): Bootcamp => app(PublishBootcampAction::class)->handle($this->bootcamp($record), $actor)),
        ];
    }

    public function with(): array
    {
        return [];
    }

    private function bootcamp(Model $record): Bootcamp
    {
        throw_unless($record instanceof Bootcamp, \LogicException::class);

        return $record;
    }
}
