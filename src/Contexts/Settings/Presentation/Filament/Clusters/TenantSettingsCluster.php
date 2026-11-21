<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Presentation\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Illuminate\Support\Facades\Gate;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

/**
 * إعدادات المؤسسة — عنصر واحد في سايدبار `/admin/t/{slug}` بتبويبات
 * (docs/05 بند ٥ · docs/24 بند ٦).
 *
 * التبويبات بتكتب في `tenant_settings` عبر `TenantSettings` — مش في
 * الإعدادات العامة. العامة (البريد، التخزين، الأمان…) في `/central` بس.
 */
final class TenantSettingsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'organization-settings';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('settings::settings.tenant.cluster');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('settings::settings.tenant.cluster');
    }

    public function getTitle(): string
    {
        return __('settings::settings.tenant.cluster');
    }

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::System->getLabel();
    }

    /** نفس درس `SettingsCluster`: من غير الشرط، الرابط بيفتح صفحة فاضية بدل 403 (docs/21 بند ٢٩). */
    public static function canAccess(): bool
    {
        return Gate::allows('access.system') && self::canAccessClusteredComponents();
    }
}
