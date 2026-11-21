<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Presentation\Filament\Central\Pages;

use Filament\Forms\Components\TextInput;

/**
 * الإعدادات المترجمة بتتخزّن كمصفوفة `['ar' => ..., 'en' => ...]`.
 * الدالة دي بتولّد حقل لكل لغة مدعومة — عشان ما نكتبش نفس الحقل مرتين.
 */
final class LocalizedFields
{
    /** @return array<int, TextInput> */
    public static function text(string $name, string $label, bool $required = false): array
    {
        return collect((array) config('app.supported_locales'))
            ->map(fn (string $locale): TextInput => TextInput::make("{$name}.{$locale}")
                ->label($label.' — '.__("common.locales.{$locale}"))
                ->required($required && $locale === config('app.fallback_locale'))
                ->maxLength(255))
            ->all();
    }
}
