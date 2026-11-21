<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Presentation\Filament\Central\Pages;

use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

/** صفحة صحة النظام — بتقرا نتايج spatie/health (docs/11 بند ٧). */
final class SystemHealth extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static ?int $navigationSort = 90;

    protected string $view = 'audit::filament.pages.system-health';

    public static function getSlug(?Panel $panel = null): string
    {
        return 'system-health';
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.items.health');
    }

    public function getTitle(): string
    {
        return __('navigation.items.health');
    }

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::System->getLabel();
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.health');
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('common.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => Artisan::call('health:check')),
        ];
    }

    /** @return Collection<int, StoredCheckResult> */
    public function results(): Collection
    {
        $stored = app(ResultStore::class)->latestResults();

        return collect($stored === null ? [] : $stored->storedCheckResults)->values();
    }

    public function lastRanAt(): ?string
    {
        $finishedAt = app(ResultStore::class)->latestResults()?->finishedAt;

        return $finishedAt === null
            ? null
            : CarbonImmutable::instance($finishedAt)->diffForHumans();
    }
}
