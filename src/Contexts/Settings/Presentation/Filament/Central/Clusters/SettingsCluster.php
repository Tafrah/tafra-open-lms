<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Presentation\Filament\Central\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Illuminate\Support\Facades\Gate;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

/**
 * كل صفحات الإعدادات تحت عنصر واحد في السايدبار، وجوّاه تبويبات
 * (docs/05 بند ٥). أي مجموعة إعدادات جديدة = صفحة بـ `$cluster` هنا،
 * مش عنصر جديد في السايدبار.
 *
 * كل تبويب بيتفلتر بـ `canAccess()` بتاع صفحته — يعني المستخدم بيشوف
 * التبويبات اللي من حقه بس.
 */
final class SettingsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'settings';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('settings::settings.cluster');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('settings::settings.cluster');
    }

    public function getTitle(): string
    {
        return __('settings::settings.cluster');
    }

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::System->getLabel();
    }

    /**
     * الافتراضي في Filament بيسمح بفتح رابط الـ cluster لأي حد، وبيخفي
     * اللينك بس. من غير الشرط ده، مستخدم مالوش أي تبويب بياخد صفحة فاضية
     * بدل 403 (docs/21 بند ٢٩).
     */
    public static function canAccess(): bool
    {
        return Gate::allows('access.system') && self::canAccessClusteredComponents();
    }
}
