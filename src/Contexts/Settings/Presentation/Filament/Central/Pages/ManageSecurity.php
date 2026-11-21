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
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Contexts\Settings\Domain\Settings\SecuritySettings;
use Src\Contexts\Settings\Presentation\Filament\Central\Clusters\SettingsCluster;

final class ManageSecurity extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static string $settings = SecuritySettings::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $slug = 'security';

    protected static ?int $navigationSort = 50;

    public static function getNavigationLabel(): string
    {
        return __('settings::settings.pages.security');
    }

    public function getTitle(): string
    {
        return __('settings::settings.pages.security');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.system') && Gate::allows('manage_security.settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('settings::settings.sections.passwords'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('password_min_length')
                        ->label(__('settings::settings.fields.password_min_length'))
                        ->numeric()->minValue(8)->maxValue(64)->required(),

                    Toggle::make('password_requires_uncompromised')
                        ->label(__('settings::settings.fields.password_uncompromised'))
                        ->helperText(__('settings::settings.help.password_uncompromised')),

                    TextInput::make('password_history_size')
                        ->label(__('settings::settings.fields.password_history'))
                        ->numeric()->minValue(0)->maxValue(24)->required(),

                    TextInput::make('password_expiry_days')
                        ->label(__('settings::settings.fields.password_expiry'))
                        ->numeric()->minValue(0)->maxValue(365)->required()
                        ->helperText(__('settings::settings.help.password_expiry')),
                ]),

            Section::make(__('settings::settings.sections.two_factor'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('two_factor_required_roles')
                        ->label(__('settings::settings.fields.two_factor_roles'))
                        ->multiple()
                        ->native(false)
                        ->options(fn (): array => Role::query()
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->map(static fn (string $name): string => __("authorization.roles.{$name}"))
                            ->all()),

                    TextInput::make('two_factor_grace_period_days')
                        ->label(__('settings::settings.fields.two_factor_grace'))
                        ->numeric()->minValue(0)->maxValue(90)->required(),
                ]),

            Section::make(__('settings::settings.sections.sessions'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('session_lifetime_minutes')
                        ->label(__('settings::settings.fields.session_lifetime'))
                        ->numeric()->minValue(5)->maxValue(10080)->required(),

                    TextInput::make('impersonation_max_minutes')
                        ->label(__('settings::settings.fields.impersonation_max'))
                        ->numeric()->minValue(5)->maxValue(240)->required()
                        ->helperText(__('settings::settings.help.impersonation_max')),
                ]),

            Section::make(__('settings::settings.sections.ai'))
                ->description(__('settings::settings.help.mcp_enabled'))
                ->columnSpanFull()
                ->schema([
                    Toggle::make('mcp_enabled')
                        ->label(__('settings::settings.fields.mcp_enabled')),
                ]),
        ]);
    }
}
