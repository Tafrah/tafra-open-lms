<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Contracts\TenantContext;

final class UsersChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Gate::allows('widget.users_chart');
    }

    public function getHeading(): string
    {
        return __('audit::audit.widgets.users_chart');
    }

    protected function getType(): string
    {
        return 'line';
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $tenantId = app(TenantContext::class)->id();

        $points = Cache::remember(
            "widget:users-chart:{$tenantId}",
            now()->addMinutes(30),
            function () use ($tenantId): array {
                $labels = [];
                $values = [];

                for ($i = 11; $i >= 0; $i--) {
                    $month = now()->subMonths($i);
                    $labels[] = $month->translatedFormat('M Y');

                    $values[] = $tenantId === null ? 0 : User::query()
                        ->ofTenant($tenantId)
                        ->whereYear('created_at', $month->year)
                        ->whereMonth('created_at', $month->month)
                        ->count();
                }

                return ['labels' => $labels, 'values' => $values];
            },
        );

        return [
            'datasets' => [[
                'label' => __('identity::identity.user.plural'),
                'data' => $points['values'],
                'borderColor' => 'rgb(18, 69, 79)',
                'tension' => 0.3,
            ]],
            'labels' => $points['labels'],
        ];
    }
}
