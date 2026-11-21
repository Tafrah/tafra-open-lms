<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Presentation\Filament\Central\Pages;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Settings\Domain\Settings\GeneralSettings;
use Src\Contexts\Settings\Presentation\Filament\Central\Clusters\SettingsCluster;

final class ManageGeneral extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = GeneralSettings::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $slug = 'general';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('settings::settings.pages.general');
    }

    public function getTitle(): string
    {
        return __('settings::settings.pages.general');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.system') && Gate::allows('update.settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('settings::settings.sections.identity'))
                ->description(__('settings::settings.sections.identity_help'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    ...LocalizedFields::text('app_name', __('settings::settings.fields.app_name'), required: true),
                    ...LocalizedFields::text('app_description', __('settings::settings.fields.app_description')),

                    TextInput::make('support_email')
                        ->label(__('settings::settings.fields.support_email'))
                        ->email()
                        ->required(),

                    TextInput::make('support_phone')
                        ->label(__('settings::settings.fields.support_phone'))
                        ->tel()
                        ->required(),
                ]),

            Section::make(__('settings::settings.sections.locale'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('default_locale')
                        ->label(__('settings::settings.fields.default_locale'))
                        ->options(fn (): array => collect((array) config('app.supported_locales'))
                            ->mapWithKeys(fn (string $locale): array => [$locale => __("common.locales.{$locale}")])
                            ->all())
                        ->native(false)
                        ->required(),

                    Select::make('timezone')
                        ->label(__('settings::settings.fields.timezone'))
                        ->options(fn (): array => collect(\DateTimeZone::listIdentifiers())
                            ->mapWithKeys(fn (string $tz): array => [$tz => $tz])
                            ->all())
                        ->searchable()
                        ->native(false)
                        ->required(),
                ]),

            Section::make(__('settings::settings.sections.maintenance'))
                ->description(__('settings::settings.sections.maintenance_help'))
                ->columnSpanFull()
                ->schema([
                    Toggle::make('maintenance_mode')
                        ->label(__('settings::settings.fields.maintenance_mode')),

                    ...LocalizedFields::text('maintenance_message', __('settings::settings.fields.maintenance_message')),
                ]),
        ]);
    }

    protected function afterSave(): void
    {
        Cache::forget('theme:brand-variables');
    }
}
