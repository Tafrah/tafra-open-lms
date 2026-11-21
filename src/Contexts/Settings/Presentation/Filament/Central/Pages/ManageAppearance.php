<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Presentation\Filament\Central\Pages;

use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Settings\Domain\Settings\AppearanceSettings;
use Src\Contexts\Settings\Presentation\Filament\Central\Clusters\SettingsCluster;
use Src\Support\Application\Contracts\DiskResolver;
use Src\Support\Infrastructure\Theming\ColorScaleGenerator;
use Src\Support\Infrastructure\Theming\ThemeColorResolver;

final class ManageAppearance extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static string $settings = AppearanceSettings::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $slug = 'appearance';

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('settings::settings.pages.appearance');
    }

    public function getTitle(): string
    {
        return __('settings::settings.pages.appearance');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.system') && Gate::allows('manage_appearance.settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('settings::settings.sections.brand'))
                ->description(__('settings::settings.sections.brand_help'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    ColorPicker::make('primary_color')
                        ->label(__('settings::settings.fields.primary_color'))
                        ->hex()
                        ->required()
                        // اختبار التباين بيرفض الحفظ — مش بيحذّر بس
                        ->rule(fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                            if (! app(ColorScaleGenerator::class)->passesContrast((string) $value)) {
                                $fail(__('settings::settings.validation.low_contrast'));
                            }
                        })
                        ->helperText(__('settings::settings.help.primary_color')),

                    Select::make('font_family')
                        ->label(__('settings::settings.fields.font'))
                        ->options([
                            'IBM Plex Sans Arabic' => 'IBM Plex Sans Arabic',
                            'Noto Sans Arabic' => 'Noto Sans Arabic',
                            'Cairo' => 'Cairo',
                        ])
                        ->native(false)
                        ->required(),

                    FileUpload::make('logo_light_path')
                        ->label(__('settings::settings.fields.logo_light'))
                        ->image()
                        ->directory('branding')
                        ->disk(fn (): string => app(DiskResolver::class)->for('branding'))
                        // SVG ممنوع: XML بيقبل سكربتات (docs/20 بند ٧)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(1024),

                    FileUpload::make('logo_dark_path')
                        ->label(__('settings::settings.fields.logo_dark'))
                        ->image()
                        ->directory('branding')
                        ->disk(fn (): string => app(DiskResolver::class)->for('branding'))
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(1024),

                    FileUpload::make('favicon_path')
                        ->label(__('settings::settings.fields.favicon'))
                        ->image()
                        ->directory('branding')
                        ->disk(fn (): string => app(DiskResolver::class)->for('branding'))
                        ->acceptedFileTypes(['image/png', 'image/x-icon'])
                        ->maxSize(256),
                ]),

            Section::make(__('settings::settings.sections.layout'))
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Select::make('default_theme')
                        ->label(__('settings::settings.fields.default_theme'))
                        ->options([
                            'light' => __('settings::settings.themes.light'),
                            'dark' => __('settings::settings.themes.dark'),
                            'system' => __('settings::settings.themes.system'),
                        ])
                        ->native(false)
                        ->required(),

                    Toggle::make('allow_theme_switch')
                        ->label(__('settings::settings.fields.allow_theme_switch')),

                    Select::make('sidebar_default')
                        ->label(__('settings::settings.fields.sidebar_default'))
                        ->options([
                            'expanded' => __('settings::settings.sidebar.expanded'),
                            'collapsed' => __('settings::settings.sidebar.collapsed'),
                        ])
                        ->native(false)
                        ->required(),
                ]),

            Section::make(__('settings::settings.sections.footer'))
                ->columnSpanFull()
                ->schema([
                    KeyValue::make('footer_text')
                        ->label(__('settings::settings.fields.footer_text'))
                        ->keyLabel(__('common.locale'))
                        ->valueLabel(__('common.text'))
                        ->addable(false)
                        ->deletable(false)
                        ->editableKeys(false),
                ]),
        ]);
    }

    protected function afterSave(): void
    {
        Cache::forget('theme:brand-variables');
        ThemeColorResolver::flushCache();

        Notification::make()
            ->success()
            ->title(__('settings::settings.saved'))
            ->body(__('settings::settings.saved_body'))
            ->send();
    }
}
