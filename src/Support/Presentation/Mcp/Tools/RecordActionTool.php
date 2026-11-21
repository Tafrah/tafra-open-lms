<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Request;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpRecordAction;
use Src\Support\Presentation\Mcp\McpResource;

/**
 * إجراء مخصص على سجل واحد (`announcements_publish`…) — بينادي نفس
 * Application Action اللي زرار الجدول بيناديه.
 */
final class RecordActionTool extends ResourceTool
{
    public function __construct(McpResource $declaration, private readonly McpRecordAction $action)
    {
        parent::__construct($declaration);
    }

    public function recordAction(): McpRecordAction
    {
        return $this->action;
    }

    public function ability(): string
    {
        return $this->action->ability();
    }

    protected function suffix(): string
    {
        return $this->action->key();
    }

    protected function permissionAction(): ?string
    {
        return $this->action->permissionAction();
    }

    public function writes(): bool
    {
        return true;
    }

    public function destructive(): bool
    {
        return $this->action->isDestructive();
    }

    public function idempotent(): bool
    {
        return $this->action->isIdempotent();
    }

    public function title(): string
    {
        return __('mcp.operations.action.title', [
            'action' => $this->action->labelText(),
            'record' => $this->declaration->singularLabel(),
        ]);
    }

    public function description(): string
    {
        $base = __('mcp.operations.action.description', [
            'action' => $this->action->labelText(),
            'record' => $this->declaration->singularLabel(),
        ]);

        $specific = $this->action->descriptionText();

        return $base.($specific !== null ? ' '.$specific : '').$this->resourceContext();
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $this->idSchema($schema),
            ...collect($this->action->inputFields())
                ->mapWithKeys(static fn (McpField $field): array => [$field->name() => $field->inputSchema($schema, creating: true)])
                ->all(),
        ];
    }

    protected function run(Request $request, User $actor): array
    {
        $rules = ['id' => $this->idRules()];

        foreach ($this->action->inputFields() as $field) {
            $rules = [...$rules, ...$field->validationRules(creating: true)];
        }

        $input = $request->validate($rules);

        $record = $this->findRecord($request, $actor, $this->action->ability());

        $values = [];

        foreach ($this->action->inputFields() as $field) {
            if (array_key_exists($field->name(), $input)) {
                $values[$field->name()] = $field->toAttribute($input[$field->name()], null);
            }
        }

        $result = $this->action->run($record, $values, $actor);
        $record = $result instanceof Model ? $result : $record->fresh() ?? $record;

        return [
            'record' => $this->declaration->present($record),
            'message' => __('mcp.results.action', ['action' => $this->action->labelText()]),
        ];
    }
}
