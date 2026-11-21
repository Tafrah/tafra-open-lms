<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Presentation\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Settings\Presentation\Filament\Clusters\TenantSettingsCluster;

/**
 * مظهر المؤسسة. لون العلامة في «ملف المؤسسة» (`EditOrganization`)،
 * والشعارات والثيم الافتراضي عامة.
 *
 * كل حقل هنا ليه قارئ فعلي في `AdminPanelProvider` — حقل بيتحفظ ومحدش
 * بيقراه نفس فخ «الإخفاء UX» (docs/24 بند ٦).
 *
 * @property-read Schema $form
 */
final class ManageTenantAppearance extends Page
{
    use ManagesTenantSettingsGroup;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $cluster = TenantSettingsCluster::class;

    protected static ?string $slug = 'appearance';

    protected static ?int $navigationSort = 10;

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

    protected static function group(): string
    {
        return 'appearance';
    }

    protected static function defaults(): array
    {
        return [
            'font_family' => 'IBM Plex Sans Arabic',
            'allow_theme_switch' => true,
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('settings::settings.sections.layout'))
                ->description(__('settings::settings.tenant.inherits'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('font_family')
                        ->label(__('settings::settings.fields.font'))
                        ->options([
                            'IBM Plex Sans Arabic' => 'IBM Plex Sans Arabic',
                            'Noto Sans Arabic' => 'Noto Sans Arabic',
                            'Cairo' => 'Cairo',
                        ])
                        ->native(false)
                        ->required(),

                    Toggle::make('allow_theme_switch')
                        ->label(__('settings::settings.fields.allow_theme_switch')),
                ]),
        ]);
    }
}
