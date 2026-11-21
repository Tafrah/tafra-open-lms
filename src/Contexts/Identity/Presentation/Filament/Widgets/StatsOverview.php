<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Contracts\TenantContext;

/**
 * إحصائيات الهوية.
 *
 * كل سياق بيعرض إحصائياته بنفسه — ودجت واحدة بتقرا من كل السياقات
 * كانت هتكسر قاعدة «سياق مايستوردش موديلات سياق تاني» (docs/01).
 */
final class StatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return Gate::allows('widget.stats_overview');
    }

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        $tenantId = app(TenantContext::class)->id();

        // الودجت بتتحسب في كل زيارة للوحة — الكاش هنا مش رفاهية
        $stats = Cache::remember(
            "widget:identity-stats:{$tenantId}",
            now()->addMinutes(5),
            fn (): array => [
                'users' => $tenantId === null ? 0 : User::query()->ofTenant($tenantId)->count(),
                'active' => $tenantId === null ? 0 : User::query()->ofTenant($tenantId)
                    ->where('status', UserStatus::Active)->count(),
                'invited' => $tenantId === null ? 0 : User::query()->ofTenant($tenantId)
                    ->where('status', UserStatus::Invited)->count(),
            ],
        );

        return [
            Stat::make(__('identity::identity.user.plural'), fc_number($stats['users']))
                ->description(__('audit::audit.widgets.users_description'))
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),

            Stat::make(__('common.active'), fc_number($stats['active']))
                ->description(__('audit::audit.widgets.active_description'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(__('identity::identity.user.status.invited'), fc_number($stats['invited']))
                ->description(__('identity::identity.user.badges.invited'))
                ->descriptionIcon('heroicon-o-envelope')
                ->color('warning'),
        ];
    }
}
