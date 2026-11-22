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

    /** أيقونات Phosphor بنفس اختيار «طفرة البسيط» (themes/simple → railIcons) */
    public function getIcon(): string
    {
        return match ($this) {
            self::Dashboard => 'phosphor-squares-four-bold',
            self::Learning => 'phosphor-book-open-bold',
            self::Exams => 'phosphor-exam-bold',
            self::Students => 'phosphor-student-bold',
            self::Finance => 'phosphor-wallet-bold',
            self::Identity => 'phosphor-users-bold',
            self::Content => 'phosphor-bell-bold',
            self::Operations => 'phosphor-gear-bold',
            self::Reports => 'phosphor-chart-bar-bold',
            self::Tenancy => 'phosphor-buildings-bold',
            self::System => 'phosphor-gear-six-bold',
            self::Tafra => 'phosphor-sparkle-bold',
        };
    }

    /** مطوية افتراضياً — بتتفتح من وقت للتاني (foldedSections في طفرة) */
    public function folded(): bool
    {
        return in_array($this, [self::Identity, self::System, self::Tafra, self::Tenancy], true);
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
