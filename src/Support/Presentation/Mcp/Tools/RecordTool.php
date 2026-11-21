<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Actions\Records\CreateRecordAction;
use Src\Support\Application\Actions\Records\DeleteRecordAction;
use Src\Support\Application\Actions\Records\ListRecordsAction;
use Src\Support\Application\Actions\Records\UpdateRecordAction;
use Src\Support\Application\DataObjects\RecordQuery;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpResource;
use Src\Support\Presentation\Mcp\RecordOperation;

/**
 * عملية قياسية واحدة على مورد (`announcements_list`، `announcements_update`…).
 *
 * الأداة مابتعملش استعلام ولا منطق: بتتحقق من المدخلات، وبتحوّلها بالإعلان،
 * وبتنادي Application Action واحد — وهو اللي بيعمل `Gate::authorize()`.
 */
final class RecordTool extends ResourceTool
{
    private const SORT_DIRECTIONS = ['asc', 'desc'];

    private const TRASHED = ['with', 'only'];

    public function __construct(McpResource $declaration, private readonly RecordOperation $operation)
    {
        parent::__construct($declaration);
    }

    public function operation(): RecordOperation
    {
        return $this->operation;
    }

    public function ability(): string
    {
        return $this->operation->ability();
    }

    protected function suffix(): string
    {
        return $this->operation->value;
    }

    public function writes(): bool
    {
        return $this->operation->writes();
    }

    public function destructive(): bool
    {
        return $this->operation->destructive();
    }

    public function idempotent(): bool
    {
        return $this->operation->idempotent();
    }

    public function title(): string
    {
        return __("mcp.operations.{$this->operation->value}.title", [
            'resource' => $this->declaration->pluralLabel(),
            'record' => $this->declaration->singularLabel(),
        ]);
    }

    public function description(): string
    {
        return __("mcp.operations.{$this->operation->value}.description", [
            'resource' => $this->declaration->pluralLabel(),
            'record' => $this->declaration->singularLabel(),
        ]).$this->resourceContext();
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return match ($this->operation) {
            RecordOperation::List => $this->listSchema($schema),
            RecordOperation::Create => $this->fieldsSchema($schema, creating: true),
            RecordOperation::Update => ['id' => $this->idSchema($schema), ...$this->fieldsSchema($schema, creating: false)],
            default => ['id' => $this->idSchema($schema)],
        };
    }

    protected function run(Request $request, User $actor): array
    {
        return match ($this->operation) {
            RecordOperation::List => $this->list($request, $actor),
            RecordOperation::Show => $this->show($request, $actor),
            RecordOperation::Create => $this->create($request, $actor),
            RecordOperation::Update => $this->update($request, $actor),
            RecordOperation::Delete => $this->delete($request, $actor),
            RecordOperation::Restore => $this->restore($request, $actor),
            RecordOperation::ForceDelete => $this->forceDelete($request, $actor),
        };
    }

    // ═══════════════════════ العمليات ═══════════════════════

    /** @return array<string, mixed> */
    private function list(Request $request, User $actor): array
    {
        $filterable = array_filter($this->declaration->fields(), static fn (McpField $field): bool => $field->isFilterable());
        $max = (int) config('ai.pagination.max', 50);

        $rules = [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort' => ['sometimes', 'nullable', 'in:'.implode(',', $this->sortableColumns())],
            'direction' => ['sometimes', 'nullable', 'in:'.implode(',', self::SORT_DIRECTIONS)],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', "max:{$max}"],
            'cursor' => ['sometimes', 'nullable', 'string', 'max:512'],
            'filters' => ['sometimes', 'nullable', 'array:'.implode(',', array_map(static fn (McpField $field): string => $field->name(), $filterable))],
        ];

        if ($this->declaration->softDeletes()) {
            $rules['trashed'] = ['sometimes', 'nullable', 'in:'.implode(',', self::TRASHED)];
        }

        foreach ($filterable as $field) {
            foreach ($field->filterRules() as $key => $fieldRules) {
                $rules["filters.{$key}"] = $fieldRules;
            }
        }

        $input = $this->validated($request, $rules);

        $filters = [];

        foreach ($filterable as $field) {
            if (array_key_exists($field->name(), (array) ($input['filters'] ?? []))) {
                $filters[$field->name()] = $field->filterValue($input['filters'][$field->name()]);
            }
        }

        $page = app(ListRecordsAction::class)->handle($actor, $this->declaration->query(), new RecordQuery(
            search: $input['search'] ?? null,
            searchColumns: $this->declaration->searchable(),
            translatableColumns: array_values(array_map(
                static fn (McpField $field): string => $field->name(),
                array_filter($this->declaration->fields(), static fn (McpField $field): bool => $field->isTranslatable()),
            )),
            filters: $filters,
            sortColumn: $input['sort'] ?? null,
            sortDirection: $input['direction'] ?? 'desc',
            trashed: $input['trashed'] ?? null,
            limit: (int) ($input['limit'] ?? config('ai.pagination.default', 20)),
            cursor: $input['cursor'] ?? null,
        ));

        return [
            'items' => $page->getCollection()->map(fn (Model $record): array => $this->declaration->present($record))->all(),
            'count' => $page->getCollection()->count(),
            'has_more' => $page->hasMorePages(),
            'next_cursor' => $page->nextCursor()?->encode(),
        ];
    }

    /** @return array<string, mixed> */
    private function show(Request $request, User $actor): array
    {
        $this->validated($request, ['id' => $this->idRules()]);

        return ['record' => $this->declaration->present($this->findRecord($request, $actor, 'view'))];
    }

    /** @return array<string, mixed> */
    private function create(Request $request, User $actor): array
    {
        $fields = $this->declaration->writableFields(creating: true);
        $input = $this->validated($request, $this->fieldRules($fields, creating: true));

        $attributes = $this->declaration->creating($this->toAttributes($fields, $input, null), $actor);
        $this->assertDateOrder($attributes, null);

        $record = app(CreateRecordAction::class)->handle($actor, $this->declaration->model(), $attributes);

        return ['record' => $this->declaration->present($record), 'message' => __('mcp.results.created')];
    }

    /** @return array<string, mixed> */
    private function update(Request $request, User $actor): array
    {
        $fields = $this->declaration->writableFields(creating: false);
        $input = $this->validated($request, ['id' => $this->idRules(), ...$this->fieldRules($fields, creating: false)]);

        $record = $this->findRecord($request, $actor, 'update');

        $abilities = [];

        foreach ($fields as $field) {
            if ($field->requiredAbility() !== null) {
                $abilities[$field->name()] = $field->requiredAbility();
            }
        }

        $attributes = $this->toAttributes($fields, $input, $record);
        $this->assertDateOrder($attributes, $record);

        $record = app(UpdateRecordAction::class)->handle($actor, $record, $attributes, $abilities);

        return ['record' => $this->declaration->present($record), 'message' => __('mcp.results.updated')];
    }

    /** @return array<string, mixed> */
    private function delete(Request $request, User $actor): array
    {
        $this->validated($request, ['id' => $this->idRules()]);
        $record = app(DeleteRecordAction::class)->remove($actor, $this->findRecord($request, $actor, 'delete'));

        return ['id' => $record->getKey(), 'deleted' => true, 'message' => __($this->declaration->softDeletes() ? 'mcp.results.trashed' : 'mcp.results.deleted')];
    }

    /** @return array<string, mixed> */
    private function restore(Request $request, User $actor): array
    {
        $this->validated($request, ['id' => $this->idRules()]);
        $record = app(DeleteRecordAction::class)->restore($actor, $this->findRecord($request, $actor, 'restore', withTrashed: true));

        return ['record' => $this->declaration->present($record), 'message' => __('mcp.results.restored')];
    }

    /** @return array<string, mixed> */
    private function forceDelete(Request $request, User $actor): array
    {
        $this->validated($request, ['id' => $this->idRules()]);
        $record = app(DeleteRecordAction::class)->purge($actor, $this->findRecord($request, $actor, 'forceDelete', withTrashed: true));

        return ['id' => $record->getKey(), 'deleted' => true, 'message' => __('mcp.results.deleted')];
    }

    // ═══════════════════════ الـ Schema ═══════════════════════

    /** @return array<string, mixed> */
    private function listSchema(JsonSchema $schema): array
    {
        $filterable = array_filter($this->declaration->fields(), static fn (McpField $field): bool => $field->isFilterable());
        $max = (int) config('ai.pagination.max', 50);

        $properties = [
            'search' => $schema->string()->max(100)->description(__('mcp.fields.search', [
                'columns' => implode(', ', $this->declaration->searchable()) ?: '—',
            ])),
            'sort' => $schema->string()->enum($this->sortableColumns())->description(__('mcp.fields.sort')),
            'direction' => $schema->string()->enum(self::SORT_DIRECTIONS)->description(__('mcp.fields.direction')),
            'limit' => $schema->integer()->min(1)->max($max)->description(__('mcp.fields.limit', ['max' => $max])),
            'cursor' => $schema->string()->max(512)->description(__('mcp.fields.cursor')),
        ];

        if ($filterable !== []) {
            $properties['filters'] = $schema->object(fn (JsonSchema $s): array => collect($filterable)
                ->mapWithKeys(static fn (McpField $field): array => [$field->name() => $field->filterSchema($s)])
                ->all())
                ->withoutAdditionalProperties()
                ->description(__('mcp.fields.filters'));
        }

        if ($this->declaration->softDeletes()) {
            $properties['trashed'] = $schema->string()->enum(self::TRASHED)->description(__('mcp.fields.trashed'));
        }

        return $properties;
    }

    /** @return array<string, mixed> */
    private function fieldsSchema(JsonSchema $schema, bool $creating): array
    {
        return collect($this->declaration->writableFields($creating))
            ->mapWithKeys(static fn (McpField $field): array => [$field->name() => $field->inputSchema($schema, $creating)])
            ->all();
    }

    /** @return list<string> */
    private function sortableColumns(): array
    {
        $columns = ['id', 'created_at', 'updated_at'];

        foreach ($this->declaration->fields() as $field) {
            if ($field->isSortable()) {
                $columns[] = $field->name();
            }
        }

        return array_values(array_unique($columns));
    }

    // ═══════════════════════ مساعدات ═══════════════════════

    /**
     * @param list<McpField> $fields
     * @return array<string, list<mixed>>
     */
    private function fieldRules(array $fields, bool $creating): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $rules = [...$rules, ...$field->validationRules($creating)];
        }

        return $rules;
    }

    /**
     * @param list<McpField> $fields
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function toAttributes(array $fields, array $input, ?Model $record): array
    {
        $attributes = [];

        foreach ($fields as $field) {
            if (array_key_exists($field->name(), $input)) {
                $attributes[$field->name()] = $field->toAttribute($input[$field->name()], $record);
            }
        }

        return $attributes;
    }

    /**
     * `->after('published_at')` على القيم الفعّالة: المبعوتة، وإلا المحفوظة.
     *
     * @param array<string, mixed> $attributes
     */
    private function assertDateOrder(array $attributes, ?Model $record): void
    {
        foreach ($this->declaration->fields() as $field) {
            $other = $field->mustBeAfter();

            if ($other === null) {
                continue;
            }

            $value = array_key_exists($field->name(), $attributes) ? $attributes[$field->name()] : $record?->getAttribute($field->name());
            $limit = array_key_exists($other, $attributes) ? $attributes[$other] : $record?->getAttribute($other);

            if ($value instanceof \DateTimeInterface && $limit instanceof \DateTimeInterface && $value <= $limit) {
                throw ValidationException::withMessages([
                    $field->name() => __('validation.after', [
                        'attribute' => $field->labelText(),
                        'date' => $this->declaration->field($other)?->labelText() ?? $other,
                    ]),
                ]);
            }
        }
    }

    /**
     * التحقق على السيرفر — الـ schema للنموذج بس، ومحدش ملزم يحترمه.
     * المفاتيح اللي مش في القواعد بتتشال: مدخل زي `tenant_id` مايوصلش لأي حاجة.
     *
     * @param array<string, list<mixed>> $rules
     * @return array<string, mixed>
     */
    private function validated(Request $request, array $rules): array
    {
        return $request->validate($rules);
    }
}
