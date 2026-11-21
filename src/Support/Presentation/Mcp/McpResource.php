<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp;

use Filament\Resources\Resource as FilamentResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Domain\Authorization\Policy;
use Throwable;

/**
 * إعلان مورد Filament لخادم MCP — الملف الوحيد اللي الميزة الجديدة بتكتبه
 * عشان الذكاء الاصطناعي يقدر يعمل اللي المستخدم بيعمله من اللوحة.
 *
 * مكانه `Contexts/<X>/Presentation/Mcp/<Model>Mcp.php` (أو `Mcp/Central/` للوحة
 * المركزية)، وبيتسجّل بالاكتشاف زي الجولات. من الإعلان ده بيتولّد:
 *
 *   {resource}_list · _get · _create · _update · _delete · _restore · _force_delete
 *   + أداة لكل `McpRecordAction` (`announcements_publish`…)
 *
 * كل أداة بتسأل نفس قدرة الـ Policy اللي زرار Filament بيسألها، وبتنفّذ عن
 * طريق Application Action. `McpCoverageTest` بيفرض إن كل قدرة في الـ Policy
 * يا إما متاحة يا إما في `omitted()` بسبب مترجم — «تحكم كامل» قرار مكتوب،
 * مش نسيان.
 */
abstract class McpResource
{
    /** @return class-string<FilamentResource> المورد اللي الإعلان بيعكسه */
    abstract public static function filamentResource(): string;

    /**
     * الحقول — نفس معنى حقول الفورم. المخرجات كمان منها (مع `id` والتواريخ).
     *
     * @return list<McpField>
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * العمليات القياسية المتاحة. الافتراضي قراءة بس — الكتابة قرار صريح.
     *
     * @return list<RecordOperation>
     */
    public function operations(): array
    {
        return [RecordOperation::List, RecordOperation::Show];
    }

    /** @return list<McpRecordAction> الإجراءات المخصصة */
    public function actions(): array
    {
        return [];
    }

    /**
     * قدرات الـ Policy اللي **مش** متاحة عن قصد، وسببها (مفتاح ترجمة).
     *
     * @return array<string, string>
     */
    public function omitted(): array
    {
        return [];
    }

    /**
     * قيم بيحطها النظام عند الإنشاء — زي `mutateFormDataBeforeCreate()` في
     * صفحة الإنشاء (الكاتب = المستخدم الحالي، مش مدخل).
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function creating(array $attributes, User $actor): array
    {
        return $attributes;
    }

    /**
     * بيانات إضافية في المخرجات (علاقة، حالة محسوبة). الحقول والـ `id`
     * والتواريخ بتتضاف لوحدها.
     *
     * @return array<string, mixed>
     */
    public function extra(Model $record): array
    {
        return [];
    }

    /**
     * العلاقات اللي `extra()` بتقراها — بتتحمّل مرة للصفحة كلها.
     *
     * @return list<string>
     */
    public function with(): array
    {
        return [];
    }

    // ═══════════════════════ مشتق من Filament ═══════════════════════

    /** @return class-string<Model> */
    public function model(): string
    {
        return static::filamentResource()::getModel();
    }

    /**
     * الاستعلام الأساسي = اللي جدول Filament بيبدأ منه بالظبط (نطاق المؤسسة،
     * العلاقات، الفلاتر الإلزامية) — النموذج يشوف اللي المستخدم بيشوفه بس.
     *
     * @return Builder<Model>
     */
    public function query(): Builder
    {
        return static::filamentResource()::getEloquentQuery()->with($this->with());
    }

    /** اسم المورد في الصلاحيات (`announcements`) — وبادئة أسماء الأدوات. */
    public function key(): string
    {
        return $this->policy()?->resourceName() ?? str((string) class_basename($this->model()))->snake()->plural()->toString();
    }

    public function policy(): ?Policy
    {
        $policy = Gate::getPolicyFor($this->model());

        return $policy instanceof Policy ? $policy : null;
    }

    public function singularLabel(): string
    {
        return static::filamentResource()::getModelLabel();
    }

    public function pluralLabel(): string
    {
        return static::filamentResource()::getPluralModelLabel();
    }

    /** @return list<string> الأعمدة اللي البحث النصي بيدوّر فيها */
    public function searchable(): array
    {
        return array_values(array_map('strval', static::filamentResource()::getGloballySearchableAttributes()));
    }

    public function softDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->model()), true);
    }

    /** رابط السجل في اللوحة — النموذج يقدر يدّيه للمستخدم يفتحه. */
    public function recordUrl(Model $record): ?string
    {
        $resource = static::filamentResource();

        foreach (['view', 'edit'] as $page) {
            if (! $resource::hasPage($page)) {
                continue;
            }

            try {
                return $resource::getUrl($page, ['record' => $record]);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    // ═══════════════════════ المخرجات ═══════════════════════

    /** @return array<string, mixed> */
    public function present(Model $record): array
    {
        $data = ['id' => $record->getKey()];

        foreach ($this->fields() as $field) {
            $data[$field->name()] = $field->present($record);
        }

        foreach (['created_at', 'updated_at', 'deleted_at'] as $timestamp) {
            $value = $record->getAttribute($timestamp);

            if ($value instanceof \DateTimeInterface) {
                $data[$timestamp] = $value->format(DATE_ATOM);
            }
        }

        return [...$data, ...$this->extra($record), 'url' => $this->recordUrl($record)];
    }

    /** @return list<McpField> */
    public function writableFields(bool $creating): array
    {
        return array_values(array_filter(
            $this->fields(),
            static fn (McpField $field): bool => $creating ? $field->isWritableOnCreate() : $field->isWritable(),
        ));
    }

    public function field(string $name): ?McpField
    {
        foreach ($this->fields() as $field) {
            if ($field->name() === $name) {
                return $field;
            }
        }

        return null;
    }

    public function action(string $ability): ?McpRecordAction
    {
        foreach ($this->actions() as $action) {
            if ($action->ability() === $ability) {
                return $action;
            }
        }

        return null;
    }

    /**
     * كل القدرات اللي الإعلان بيغطيها (متاحة) — للاختبار المعماري.
     *
     * @return list<string>
     */
    public function exposedAbilities(): array
    {
        $abilities = array_map(static fn (RecordOperation $operation): string => $operation->ability(), $this->operations());

        foreach ($this->actions() as $action) {
            $abilities[] = $action->ability();
        }

        foreach ($this->fields() as $field) {
            if ($field->requiredAbility() !== null) {
                $abilities[] = $field->requiredAbility();
            }
        }

        return array_values(array_unique($abilities));
    }
}
