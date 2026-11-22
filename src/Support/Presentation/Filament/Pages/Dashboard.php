<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Filament\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;

/** «الرئيسية» — نفس لوحة Filament بأيقونة وتسمية طفرة (themes/simple). */
final class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = 'phosphor-squares-four-bold';

    public static function getNavigationLabel(): string
    {
        return __('shell.home');
    }

    /** الرئيسية في طفرة من غير عنوان كبير — أول حاجة «إدارة المنصة» */
    public function getHeading(): string
    {
        return '';
    }
}
