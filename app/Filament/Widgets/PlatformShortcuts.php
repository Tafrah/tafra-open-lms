<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Shell\AdminShell;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Gate;

/**
 * «إدارة المنصة»: مربع لكل صفحة أساسية (PlatformShortcuts في طفرة).
 * كل مربع بيظهر بس لو المستخدم يقدر يفتح صفحته.
 */
final class PlatformShortcuts extends Widget
{
    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'theme.simple.platform-shortcuts';

    public static function canView(): bool
    {
        return Gate::allows('widget.platform_shortcuts');
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return ['tiles' => AdminShell::shortcuts()];
    }
}
