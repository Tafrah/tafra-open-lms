<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Presentation\Filament\Central\Pages;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Settings\Domain\Settings\NotificationSettings;
use Src\Contexts\Settings\Presentation\Filament\Central\Clusters\SettingsCluster;

final class ManageNotifications extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static string $settings = NotificationSettings::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $slug = 'notifications';

    protected static ?int $navigationSort = 60;

    public static function getNavigationLabel(): string
    {
        return __('settings::settings.pages.notifications');
    }

    public function getTitle(): string
    {
        return __('settings::settings.pages.notifications');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.system') && Gate::allows('manage_notifications.settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('settings::settings.sections.channels'))
                ->description(__('settings::settings.sections.channels_help'))
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('database_enabled')->label(__('notifications::notifications.channels.database')),
                    Toggle::make('mail_enabled')->label(__('notifications::notifications.channels.mail')),
                    Toggle::make('broadcast_enabled')->label(__('notifications::notifications.channels.broadcast')),
                ]),

            Section::make(__('settings::settings.sections.retention'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('prune_read_after_days')
                        ->label(__('settings::settings.fields.prune_read_after'))
                        ->numeric()->minValue(1)->maxValue(3650)->required()
                        ->suffix(__('settings::settings.units.days')),

                    TextInput::make('digest_hour')
                        ->label(__('settings::settings.fields.digest_hour'))
                        ->numeric()->minValue(0)->maxValue(23)->required(),
                ]),
        ]);
    }
}
