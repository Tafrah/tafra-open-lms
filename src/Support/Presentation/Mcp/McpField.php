<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp;

use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * حقل في إعلان مورد MCP — نفس معنى حقل في فورم Filament، بس للنموذج.
 *
 * الحقل الواحد بيطلّع: الـ JSON Schema اللي النموذج بيشوفه، وقواعد التحقق
 * على السيرفر، وإزاي القيمة بتتكتب في الموديل، وإزاي بتتعرض. الـ schema
 * للنموذج والـ validation للسيرفر — الاتنين إلزاميين (docs/23 بند ٣).
 */
final class McpField
{
    private const STRING = 'string';

    private const TEXT = 'text';

    private const HTML = 'html';

    private const INTEGER = 'integer';

    private const NUMBER = 'number';

    private const BOOLEAN = 'boolean';

    private const ENUM = 'enum';

    private const DATETIME = 'datetime';

    private const DATE = 'date';

    private const EMAIL = 'email';

    private bool $translatable = false;

    private bool $requiredOnCreate = false;

    private bool $nullable = true;

    private bool $writable = true;

    private bool $filterable = false;

    private bool $sortable = false;

    private ?int $max = null;

    private ?int $min = null;

    private ?string $labelKey = null;

    private ?string $hintKey = null;

    private ?string $ability = null;

    private ?string $after = null;

    /** @var class-string<BackedEnum>|null */
    private ?string $enum = null;

    /** @var list<string> */
    private array $extraRules = [];

    private function __construct(
        private readonly string $name,
        private readonly string $type,
    ) {}

    public static function string(string $name): self
    {
        return new self($name, self::STRING);
    }

    /** نص طويل من غير تنسيق */
    public static function text(string $name): self
    {
        return new self($name, self::TEXT);
    }

    /**
     * محتوى محرّر غني. المدخل HTML بيتنقّى في الموديل عند الحفظ (`SanitizesHtml`)،
     * والمخرج بيتحوّل لنص عادي — HTML منقّى برضه ممكن يحمل تعليمات للنموذج.
     */
    public static function html(string $name): self
    {
        return new self($name, self::HTML);
    }

    public static function integer(string $name): self
    {
        return new self($name, self::INTEGER);
    }

    public static function number(string $name): self
    {
        return new self($name, self::NUMBER);
    }

    public static function boolean(string $name): self
    {
        return (new self($name, self::BOOLEAN))->nullable(false);
    }

    public static function email(string $name): self
    {
        return new self($name, self::EMAIL);
    }

    /** @param class-string<BackedEnum> $enum */
    public static function enum(string $name, string $enum): self
    {
        $field = new self($name, self::ENUM);
        $field->enum = $enum;

        return $field;
    }

    public static function dateTime(string $name): self
    {
        return new self($name, self::DATETIME);
    }

    public static function date(string $name): self
    {
        return new self($name, self::DATE);
    }

    // ═══════════════════════ الإعداد ═══════════════════════

    /** قيمة لكل لغة مدعومة (`spatie/laravel-translatable`) */
    public function translatable(bool $translatable = true): self
    {
        $this->translatable = $translatable;

        return $this;
    }

    /**
     * مطلوب عند الإنشاء. في حقل مترجم: لغة الـ fallback بس هي المطلوبة —
     * نفس قاعدة الفورم.
     */
    public function required(bool $required = true): self
    {
        $this->requiredOnCreate = $required;
        $this->nullable = ! $required;

        return $this;
    }

    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function max(int $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function min(int $min): self
    {
        $this->min = $min;

        return $this;
    }

    /** مفتاح ترجمة التسمية — نفس اللي الفورم بيستخدمه */
    public function label(string $translationKey): self
    {
        $this->labelKey = $translationKey;

        return $this;
    }

    /** مفتاح ترجمة شرح إضافي للنموذج: قاعدة، صيغة، أثر جانبي */
    public function hint(string $translationKey): self
    {
        $this->hintKey = $translationKey;

        return $this;
    }

    /** للعرض بس — مابيظهرش في مدخلات الإنشاء والتعديل */
    public function readOnly(): self
    {
        $this->writable = false;

        return $this;
    }

    /**
     * الحقل بيتكتب بس لو المستخدم يقدر يعمل القدرة دي على السجل — نفس
     * `->saved(fn () => can('pin', $record))` في الفورم (docs/21 بند ٩).
     * التعديل بس: قدرة على سجل مالهاش معنى قبل ما السجل يتعمل.
     */
    public function ability(string $ability): self
    {
        $this->ability = $ability;

        return $this;
    }

    public function filterable(bool $filterable = true): self
    {
        $this->filterable = $filterable;

        return $this;
    }

    /**
     * ترتيب بالعمود ده في القائمة. ⚠️ لازم يكون `NOT NULL`: ترقيم الـ cursor
     * مابيدعمش NULL — صفحة فيها آخر صف NULL بتقفل اللي بعدها (`docs/21` بند ٣١).
     */
    public function sortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    /**
     * التاريخ لازم يكون بعد حقل تاني — زي `->after('published_at')` في الفورم.
     * بيتفحص على القيمة الفعّالة (المبعوتة أو المحفوظة)، فالتعديل الجزئي متغطّي.
     */
    public function after(string $field): self
    {
        $this->after = $field;

        return $this;
    }

    public function mustBeAfter(): ?string
    {
        return $this->after;
    }

    /** @param list<string> $rules قواعد Laravel إضافية على القيمة (مش على المصفوفة) */
    public function rules(array $rules): self
    {
        $this->extraRules = $rules;

        return $this;
    }

    // ═══════════════════════ القراءة ═══════════════════════

    public function name(): string
    {
        return $this->name;
    }

    public function labelText(): string
    {
        return $this->labelKey !== null ? __($this->labelKey) : Str::headline($this->name);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function isWritableOnCreate(): bool
    {
        return $this->writable && $this->ability === null;
    }

    public function isFilterable(): bool
    {
        return $this->filterable && ! $this->translatable && ! in_array($this->type, [self::HTML, self::TEXT], true);
    }

    public function isSortable(): bool
    {
        return $this->sortable && ! $this->translatable;
    }

    public function isTranslatable(): bool
    {
        return $this->translatable;
    }

    public function requiredAbility(): ?string
    {
        return $this->ability;
    }

    // ═══════════════════════ الـ Schema ═══════════════════════

    public function inputSchema(JsonSchema $schema, bool $creating): Type
    {
        if ($this->translatable) {
            $locales = $this->locales();
            $fallback = (string) config('app.fallback_locale');

            $type = $schema->object(fn (JsonSchema $s): array => collect($locales)
                ->mapWithKeys(fn (string $locale): array => [
                    $locale => $this->scalarSchema($s)
                        ->required($creating && $this->requiredOnCreate && $locale === $fallback),
                ])
                ->all())
                ->withoutAdditionalProperties();
        } else {
            $type = $this->scalarSchema($schema);
        }

        return $type
            ->description($this->describe($creating))
            ->required($creating && $this->requiredOnCreate);
    }

    public function filterSchema(JsonSchema $schema): Type
    {
        return $this->scalarSchema($schema)->description(__('mcp.fields.filter', ['field' => $this->labelText()]));
    }

    /**
     * قواعد التحقق — بمسارات منقّطة للحقول المترجمة.
     *
     * @return array<string, list<mixed>>
     */
    public function validationRules(bool $creating): array
    {
        $presence = $creating && $this->requiredOnCreate ? 'required' : 'sometimes';

        if (! $this->translatable) {
            return [$this->name => [$presence, ...($this->nullable && $presence !== 'required' ? ['nullable'] : []), ...$this->valueRules()]];
        }

        $fallback = (string) config('app.fallback_locale');
        $rules = [$this->name => [$presence, 'array:'.implode(',', $this->locales())]];

        foreach ($this->locales() as $locale) {
            $rules["{$this->name}.{$locale}"] = [
                ...match (true) {
                    // لغة الـ fallback مطلوبة عند الإنشاء — وفي التعديل مينفعش تتمسح
                    $this->requiredOnCreate && $locale === $fallback && $creating => ['required'],
                    $this->requiredOnCreate && $locale === $fallback => ['sometimes', 'filled'],
                    default => ['nullable'],
                },
                ...$this->valueRules(),
            ];
        }

        return $rules;
    }

    /** @return array<string, list<mixed>> */
    public function filterRules(): array
    {
        return [$this->name => ['sometimes', 'nullable', ...$this->valueRules()]];
    }

    // ═══════════════════════ الكتابة والعرض ═══════════════════════

    /**
     * القيمة اللي بتتكتب في الموديل. الحقل المترجم في التعديل بيندمج مع
     * الترجمات الموجودة — إرسال `en` بس مايمسحش `ar`.
     */
    public function toAttribute(mixed $value, ?Model $record): mixed
    {
        if ($this->translatable) {
            $existing = $record !== null && method_exists($record, 'getTranslations')
                ? (array) $record->getTranslations($this->name)
                : [];

            return array_filter(
                [...$existing, ...(array) $value],
                static fn (mixed $translation): bool => $translation !== null && $translation !== '',
            );
        }

        return match ($this->type) {
            self::ENUM => $value === null ? null : ($this->enum)::from($value),
            self::DATETIME, self::DATE => $value === null ? null : Carbon::parse((string) $value),
            self::BOOLEAN => (bool) $value,
            default => $value,
        };
    }

    public function present(Model $record): mixed
    {
        if ($this->translatable && method_exists($record, 'getTranslations')) {
            return collect((array) $record->getTranslations($this->name))
                ->map(fn (mixed $value): mixed => $this->presentScalar($value))
                ->all();
        }

        return $this->presentScalar($record->getAttribute($this->name));
    }

    /** قيمة الفلتر جاهزة للاستعلام */
    public function filterValue(mixed $value): mixed
    {
        return match ($this->type) {
            self::ENUM => $value === null ? null : ($this->enum)::from($value)->value,
            self::BOOLEAN => (bool) $value,
            default => $value,
        };
    }

    // ═══════════════════════ مساعدات ═══════════════════════

    private function scalarSchema(JsonSchema $schema): Type
    {
        $type = match ($this->type) {
            self::INTEGER => $schema->integer(),
            self::NUMBER => $schema->number(),
            self::BOOLEAN => $schema->boolean(),
            default => $schema->string(),
        };

        if ($this->type === self::ENUM && $this->enum !== null) {
            $type->enum(array_map(static fn (BackedEnum $case): string|int => $case->value, ($this->enum)::cases()));
        }

        if ($this->type === self::DATETIME && method_exists($type, 'format')) {
            $type->format('date-time');
        }

        if ($this->type === self::DATE && method_exists($type, 'format')) {
            $type->format('date');
        }

        if ($this->type === self::EMAIL && method_exists($type, 'format')) {
            $type->format('email');
        }

        if ($this->max !== null && method_exists($type, 'max')) {
            $type->max($this->max);
        }

        if ($this->min !== null && method_exists($type, 'min')) {
            $type->min($this->min);
        }

        return $this->nullable ? $type->nullable() : $type;
    }

    /** @return list<mixed> */
    private function valueRules(): array
    {
        $rules = match ($this->type) {
            self::STRING, self::TEXT, self::HTML => ['string'],
            self::EMAIL => ['string', 'email:rfc'],
            self::INTEGER => ['integer'],
            self::NUMBER => ['numeric'],
            self::BOOLEAN => ['boolean'],
            self::ENUM => [Rule::enum((string) $this->enum)],
            self::DATETIME, self::DATE => ['date'],
            default => [],
        };

        if ($this->max !== null) {
            $rules[] = 'max:'.$this->max;
        }

        if ($this->min !== null) {
            $rules[] = 'min:'.$this->min;
        }

        return [...$rules, ...$this->extraRules];
    }

    private function describe(bool $creating): string
    {
        $parts = [$this->labelText()];

        if ($this->type === self::ENUM && $this->enum !== null) {
            $parts[] = __('mcp.fields.enum', ['values' => collect(($this->enum)::cases())
                ->map(static fn (BackedEnum $case): string => $case instanceof HasLabel
                    ? "{$case->value} ({$case->getLabel()})"
                    : (string) $case->value)
                ->implode(', ')]);
        }

        if ($this->type === self::HTML) {
            $parts[] = __('mcp.fields.html');
        }

        if (in_array($this->type, [self::DATETIME, self::DATE], true)) {
            $parts[] = __('mcp.fields.date');
        }

        if ($this->translatable) {
            $parts[] = __('mcp.fields.translatable', [
                'locales' => implode(', ', $this->locales()),
                'fallback' => (string) config('app.fallback_locale'),
            ]);
        }

        if ($this->ability !== null) {
            $parts[] = __('mcp.fields.ability');
        }

        if (! $creating) {
            $parts[] = __('mcp.fields.omit_to_keep');
        }

        if ($this->hintKey !== null) {
            $parts[] = __($this->hintKey);
        }

        return implode(' — ', $parts);
    }

    private function presentScalar(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof CarbonInterface => $value->toIso8601String(),
            $this->type === self::HTML => ToolOutput::plainText(is_string($value) ? $value : null),
            is_string($value) => ToolOutput::limitText($value),
            default => $value,
        };
    }

    /** @return list<string> */
    private function locales(): array
    {
        return array_values(array_map('strval', (array) config('app.supported_locales')));
    }
}
