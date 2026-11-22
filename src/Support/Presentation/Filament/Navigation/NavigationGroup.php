<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Filament\Navigation;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * مجموعات السايدبار كـ Enum مش strings — الترتيب والأيقونة والترجمة
 * والصلاحية كلهم في مكان واحد (docs/07 بند ٢).
 *
 * القاعدة: ٧±٢ مجموعة ظاهرة كحد أقصى لأي دور.
 */
enum NavigationGroup: string implements HasIcon, HasLabel
{
    case Dashboard = 'dashboard';
    // المنصة التعليمية — أول حاجة المدرس بيدوّر عليها
    case Learning = 'learning';
    case Exams = 'exams';
    case Students = 'students';
    case Finance = 'finance';
    case Identity = 'identity';
    case Content = 'content';
    case Operations = 'operations';
    case Reports = 'reports';
    case Tenancy = 'tenancy';
    case System = 'system';
    // «مميزات طفرة» — آخر حاجة في السايدبار (docs/product/plan.html بند ٩)
    case Tafra = 'tafra';

    public function getLabel(): string
    {
        return __("navigation.groups.{$this->value}");
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Dashboard => 'heroicon-o-home',
            self::Learning => 'heroicon-o-academic-cap',
            self::Exams => 'heroicon-o-clipboard-document-check',
            self::Students => 'heroicon-o-user-group',
            self::Finance => 'heroicon-o-banknotes',
            self::Identity => 'heroicon-o-users',
            self::Content => 'heroicon-o-document-text',
            self::Operations => 'heroicon-o-cog-6-tooth',
            self::Reports => 'heroicon-o-chart-bar',
            self::Tenancy => 'heroicon-o-building-office-2',
            self::System => 'heroicon-o-server-stack',
            self::Tafra => 'heroicon-o-sparkles',
        };
    }

    public function sort(): int
    {
        return match ($this) {
            self::Dashboard => 0,
            self::Learning => 2,
            self::Exams => 4,
            self::Students => 6,
            self::Finance => 8,
            self::Identity => 10,
            self::Content => 20,
            self::Operations => 30,
            self::Reports => 40,
            self::Tenancy => 80,
            self::System => 90,
            self::Tafra => 95,
        };
    }

    /** القدرة المطلوبة لظهور المجموعة كلها (Gate معرّف، مش نص صلاحية) */
    public function ability(): ?string
    {
        return match ($this) {
            self::System => 'access.system',
            self::Tenancy => 'access.tenancy',
            default => null,
        };
    }
}
