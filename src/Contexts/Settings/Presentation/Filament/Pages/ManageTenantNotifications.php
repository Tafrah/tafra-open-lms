<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Presentation\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Settings\Presentation\Filament\Clusters\TenantSettingsCluster;

/**
 * قنوات الإشعارات للمؤسسة — بيقراها `NotificationChannelResolver`
 * لكل إشعار (docs/09 بند ٣). الاحتفاظ والملخّص اليومي عامين.
 *
 * @property-read Schema $form
 */
final class ManageTenantNotifications extends Page
{
    use ManagesTenantSettingsGroup;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static ?string $cluster = TenantSettingsCluster::class;

    protected static ?string $slug = 'notifications';

    protected static ?int $navigationSort = 20;

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

    protected static function group(): string
    {
        return 'notifications';
    }

    protected static function defaults(): array
    {
        return [
            'database_enabled' => true,
            'mail_enabled' => true,
            'broadcast_enabled' => false,
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('settings::settings.sections.channels'))
                ->description(__('settings::settings.tenant.inherits'))
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('database_enabled')->label(__('notifications::notifications.channels.database')),
                    Toggle::make('mail_enabled')->label(__('notifications::notifications.channels.mail')),
                    Toggle::make('broadcast_enabled')->label(__('notifications::notifications.channels.broadcast')),
                ]),
        ]);
    }
}
