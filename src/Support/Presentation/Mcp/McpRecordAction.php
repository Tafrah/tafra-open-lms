<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Src\Contexts\Identity\Domain\Models\User;

/**
 * إجراء مخصص على سجل واحد (نشر، تثبيت، إيقاف…) = أداة MCP واحدة.
 *
 * زي `Action::make('publish')->authorize('publish')` في الجدول بالظبط:
 * قدرة Policy واحدة، و`using()` بينادي Application Action واحد — نفس اللي
 * الزرار بيناديه. مفيش منطق هنا (docs/23 بند ١).
 *
 * سجل واحد بس. مفيش «انشر كل…» — الجماعي من اللوحة بـ
 * `authorizeIndividualRecords()` (docs/23 بند ٥).
 */
final class McpRecordAction
{
    private ?string $labelKey = null;

    private ?string $descriptionKey = null;

    private ?string $permission = null;

    private bool $destructive = true;

    private bool $idempotent = false;

    /** @var list<McpField> */
    private array $fields = [];

    /** @var (Closure(Model, array<string, mixed>, User): mixed)|null */
    private ?Closure $handler = null;

    private function __construct(private readonly string $ability) {}

    public static function make(string $ability): self
    {
        return new self($ability);
    }

    public function label(string $translationKey): self
    {
        $this->labelKey = $translationKey;

        return $this;
    }

    /** شرح للنموذج: الإجراء بيعمل إيه، وشروطه، وأثره الجانبي. */
    public function description(string $translationKey): self
    {
        $this->descriptionKey = $translationKey;

        return $this;
    }

    /** قدرة بتسأل عن صلاحية باسم مختلف (`activate` ← `suspend`) — للإظهار بس. */
    public function permission(string $action): self
    {
        $this->permission = $action;

        return $this;
    }

    /** @param list<McpField> $fields مدخلات الإجراء غير رقم السجل */
    public function fields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * التنفيذ — Application Action واحد، وهو اللي بيعمل `Gate::authorize()`.
     *
     * @param Closure(Model, array<string, mixed>, User): mixed $handler
     */
    public function using(Closure $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    /** إجراء بيضيف ومابيمسحش ولا بيغيّر حاجة موجودة بشكل مايترجعش. */
    public function safe(): self
    {
        $this->destructive = false;

        return $this;
    }

    public function idempotent(bool $idempotent = true): self
    {
        $this->idempotent = $idempotent;

        return $this;
    }

    public function ability(): string
    {
        return $this->ability;
    }

    public function key(): string
    {
        return Str::snake($this->ability);
    }

    public function permissionAction(): ?string
    {
        return $this->permission;
    }

    public function labelText(): string
    {
        return $this->labelKey !== null ? __($this->labelKey) : Str::headline($this->ability);
    }

    public function descriptionText(): ?string
    {
        return $this->descriptionKey !== null ? __($this->descriptionKey) : null;
    }

    public function isDestructive(): bool
    {
        return $this->destructive;
    }

    public function isIdempotent(): bool
    {
        return $this->idempotent;
    }

    /** @return list<McpField> */
    public function inputFields(): array
    {
        return $this->fields;
    }

    /** @param array<string, mixed> $input */
    public function run(Model $record, array $input, User $actor): mixed
    {
        if ($this->handler === null) {
            throw new \LogicException("MCP action [{$this->ability}] has no handler.");
        }

        return ($this->handler)($record, $input, $actor);
    }
}
