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
 * مفتاح وصول الذكاء الاصطناعي للمؤسسة (docs/23 بند ٥).
 *
 * بيقراه `AiAvailability` قبل كل نداء MCP — قفله بيفصل كل الروابط في
 * المؤسسة فوراً من غير ما يلغيها (فتحه تاني بيرجّعها).
 *
 * @property-read Schema $form
 */
final class ManageTenantAi extends Page
{
    use ManagesTenantSettingsGroup;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $cluster = TenantSettingsCluster::class;

    protected static ?string $slug = 'ai';

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('settings::settings.pages.ai');
    }

    public function getTitle(): string
    {
        return __('settings::settings.pages.ai');
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.system') && Gate::allows('manage_ai.settings');
    }

    protected static function group(): string
    {
        return 'ai';
    }

    protected static function defaults(): array
    {
        return [
            'mcp_enabled' => true,
            'allow_writes' => true,
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('settings::settings.sections.ai'))
                ->description(__('settings::settings.help.mcp_tenant'))
                ->columnSpanFull()
                ->schema([
                    Toggle::make('mcp_enabled')
                        ->label(__('settings::settings.fields.mcp_enabled')),

                    Toggle::make('allow_writes')
                        ->label(__('settings::settings.fields.mcp_allow_writes'))
                        ->helperText(__('settings::settings.help.mcp_allow_writes')),
                ]),
        ]);
    }
}
