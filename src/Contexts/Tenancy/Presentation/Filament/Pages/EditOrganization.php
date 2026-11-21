<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Presentation\Filament\Pages;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Src\Support\Infrastructure\Theming\ColorScaleGenerator;
use Src\Support\Infrastructure\Theming\ThemeColorResolver;

/**
 * ملف المؤسسة من جوه لوحتها — الاسم والوصف ولون العلامة بس.
 *
 * المُعرّف والنطاق والإيقاف والحذف شغل فريق التشغيل في `/central`
 * (docs/24 بند ٣). من غير الصفحة دي مدير المؤسسة مكانش هيقدر يغيّر
 * اسم مؤسسته بعد ما شاشة المؤسسات اتنقلت.
 */
final class EditOrganization extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return __('tenancy::tenancy.tenant.profile.label');
    }

    public static function canView(Model $tenant): bool
    {
        return Gate::allows('access.organization_profile');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('tenancy::tenancy.tenant.sections.identity'))
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
                                        ->required($locale === config('app.fallback_locale'))
                                        ->maxLength(120),

                                    TextInput::make("description.{$locale}")
                                        ->label(__('tenancy::tenancy.tenant.fields.description'))
                                        ->maxLength(255),
                                ]))
                            ->all()),
                ]),

            Section::make(__('tenancy::tenancy.tenant.sections.brand'))
                ->description(__('tenancy::tenancy.tenant.sections.brand_help'))
                ->columnSpanFull()
                ->schema([
                    ColorPicker::make('primary_color')
                        ->label(__('tenancy::tenancy.tenant.fields.primary_color'))
                        ->hex()
                        ->required()
                        ->rule(fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                            if (! app(ColorScaleGenerator::class)->passesContrast((string) $value)) {
                                $fail(__('tenancy::tenancy.tenant.validation.low_contrast'));
                            }
                        })
                        ->helperText(__('tenancy::tenancy.tenant.help.primary_color')),
                ]),
        ]);
    }

    protected function afterSave(): void
    {
        ThemeColorResolver::flushCache();
    }
}
