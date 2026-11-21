<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Persistence\Concerns;

use Illuminate\Database\Eloquent\Model;
use Src\Support\Infrastructure\Html\HtmlSanitizer;

/**
 * تنقية محتوى HTML **عند الحفظ**. النصف التاني (عند العرض) في
 * الـ Blade/Infolist عبر نفس الـ HtmlSanitizer (docs/20 بند ٥).
 *
 * الموديل اللي بيستخدم الـ trait ده لازم يعرّف:
 *   `protected array $sanitizedHtml = ['body'];`
 * ولو الحقول مترجمة، لازم يستخدم spatie/laravel-translatable كمان.
 *
 * @phpstan-require-extends Model
 *
 * @property list<string> $sanitizedHtml
 * @property list<string> $translatable
 */
trait SanitizesHtml
{
    public static function bootSanitizesHtml(): void
    {
        static::saving(function (self $model): void {
            $model->sanitizeHtmlAttributes();
        });
    }

    public function sanitizeHtmlAttributes(): void
    {
        $sanitizer = app(HtmlSanitizer::class);

        foreach ($this->sanitizedHtmlAttributes() as $attribute) {
            if (! array_key_exists($attribute, $this->getAttributes())) {
                continue;
            }

            // الحقول المترجمة بتتخزّن JSON — لازم نقّي كل لغة على حدة
            if ($this->isSanitizedAttributeTranslatable($attribute)) {
                $this->setTranslations($attribute, array_map(
                    static fn (?string $value): string => $sanitizer->clean($value),
                    $this->getTranslations($attribute),
                ));

                continue;
            }

            $this->setAttribute($attribute, $sanitizer->clean($this->getAttribute($attribute)));
        }
    }

    /** @return list<string> */
    public function sanitizedHtmlAttributes(): array
    {
        return $this->sanitizedHtml;
    }

    /**
     * الاسم مختلف عن `getTranslatableAttributes()` عن قصد عشان
     * ما يتصادمش مع spatie/laravel-translatable.
     */
    public function isSanitizedAttributeTranslatable(string $attribute): bool
    {
        // موديل من غير ترجمة (مفيش `$translatable`) = عمود نص عادي
        return property_exists($this, 'translatable')
            && is_array($this->translatable)
            && in_array($attribute, $this->translatable, true);
    }
}
