<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Presentation\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Content\Domain\Enums\AnnouncementStatus;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Support\Application\Contracts\TenantContext;

final class AnnouncementStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Gate::allows('widget.stats_overview');
    }

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        $tenantId = app(TenantContext::class)->id();

        $stats = Cache::remember(
            "widget:content-stats:{$tenantId}",
            now()->addMinutes(5),
            fn (): array => [
                'published' => Announcement::query()->where('status', AnnouncementStatus::Published)->count(),
                'drafts' => Announcement::query()->where('status', AnnouncementStatus::Draft)->count(),
                'pinned' => Announcement::query()->where('is_pinned', true)->count(),
            ],
        );

        return [
            Stat::make(__('content::content.announcement.status.published'), fc_number($stats['published']))
                ->description(__('audit::audit.widgets.announcements_description'))
                ->descriptionIcon('heroicon-o-megaphone')
                ->color('info'),

            Stat::make(__('content::content.announcement.status.draft'), fc_number($stats['drafts']))
                ->description(__('content::content.announcement.badges.drafts'))
                ->descriptionIcon('heroicon-o-pencil')
                ->color('gray'),

            Stat::make(__('content::content.announcement.fields.pinned'), fc_number($stats['pinned']))
                ->descriptionIcon('heroicon-o-bookmark')
                ->color('warning'),
        ];
    }
}
