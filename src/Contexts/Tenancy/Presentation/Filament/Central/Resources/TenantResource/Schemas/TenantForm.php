<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Infrastructure\Theming\ColorScaleGenerator;
use Src\Support\Infrastructure\Theming\ThemeColorResolver;

final class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('tenancy::tenancy.tenant.sections.identity'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Tabs::make('translations')
                        ->columnSpanFull()
                        ->tabs(collect((array) config('app.supported_locales'))
                            ->map(fn (string $locale): Tabs\Tab => Tabs\Tab::make($locale)
                                ->label(__("common.locales.{$locale}"))
                                ->schema([
                                    TextInput::make("name.{$locale}")
                                        ->label(__('tenancy::tenancy.tenant.fields.name'))
                                        // اللغة الاحتياطية إجبارية، الباقي اختياري مع fallback
                                        ->required($locale === config('app.fallback_locale'))
                                        ->maxLength(120),

                                    TextInput::make("description.{$locale}")
                                        ->label(__('tenancy::tenancy.tenant.fields.description'))
                                        ->maxLength(255),
                                ]))
                            ->all()),

                    TextInput::make('slug')
                        ->label(__('tenancy::tenancy.tenant.fields.slug'))
                        ->required()
                        ->alphaDash()
                        ->unique(ignoreRecord: true)
                        ->maxLength(60)
                        ->extraAttributes(['class' => 'fc-code'])
                        ->helperText(__('tenancy::tenancy.tenant.help.slug')),

                    TextInput::make('domain')
                        ->label(__('tenancy::tenancy.tenant.fields.domain'))
                        ->unique(ignoreRecord: true)
                        ->maxLength(180)
                        ->extraAttributes(['class' => 'fc-code'])
                        ->helperText(__('tenancy::tenancy.tenant.help.domain')),
                ]),

            Section::make(__('tenancy::tenancy.tenant.sections.brand'))
                ->description(__('tenancy::tenancy.tenant.sections.brand_help'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    ColorPicker::make('primary_color')
                        ->label(__('tenancy::tenancy.tenant.fields.primary_color'))
                        ->hex()
                        ->required()
                        ->default(ThemeColorResolver::DEFAULT_BRAND)
                        // معيار قبول: الدرجات 600 و700 تباينها ≥ 4.5:1 على أبيض
                        ->rule(fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                            if (! app(ColorScaleGenerator::class)->passesContrast((string) $value)) {
                                $fail(__('tenancy::tenancy.tenant.validation.low_contrast'));
                            }
                        })
                        ->helperText(__('tenancy::tenancy.tenant.help.primary_color')),

                    Toggle::make('is_active')
                        ->label(__('tenancy::tenancy.tenant.fields.is_active'))
                        ->default(true)
                        // حقل حساس: الإخفاء UX، ومنع الحفظ هو الأمان
                        ->visible(fn (?Tenant $record): bool => auth()->user()?->can('update', $record ?? Tenant::class) ?? false)
                        // الإطفاء بيعدّي على `deactivate` (قاعدة آخر مؤسسة نشطة) — `update` لوحده كان بيتخطاها
                        ->saved(fn (?Tenant $record, mixed $state): bool => match (true) {
                            $record === null => auth()->user()?->can('create', Tenant::class) ?? false,
                            $record->is_active && ! $state => auth()->user()?->can('deactivate', $record) ?? false,
                            default => auth()->user()?->can('update', $record) ?? false,
                        }),

                    DateTimePicker::make('trial_ends_at')
                        ->label(__('tenancy::tenancy.tenant.fields.trial_ends_at'))
                        ->seconds(false)
                        ->helperText(__('tenancy::tenancy.tenant.help.trial')),
                ]),
        ]);
    }
}
