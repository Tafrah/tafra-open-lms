<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Presentation\Filament\Widgets;

use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Audit\Domain\Models\ActivityLog;
use Src\Support\Application\Contracts\TenantContext;

final class RecentActivity extends TableWidget
{
    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Gate::allows('widget.recent_activity');
    }

    public function getTableHeading(): string
    {
        return __('audit::audit.widgets.recent_activity');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ActivityLog::query()
                ->with('causer:id,name')
                ->forTenant(app(TenantContext::class)->id())
                ->latest())
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->deferLoading(false)
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->since()
                    ->weight(FontWeight::Medium),

                TextColumn::make('description')
                    ->label(__('audit::audit.log.fields.description'))
                    ->formatStateUsing(fn (string $state): string => __('audit::audit.log.events.'.$state)),

                TextColumn::make('log_name')
                    ->label(__('audit::audit.log.fields.channel'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => __('audit::audit.log.channels.'.($state ?? 'default'))),

                TextColumn::make('causer.name')
                    ->label(__('audit::audit.log.fields.causer'))
                    ->placeholder(__('audit::audit.log.system')),
            ])
            ->emptyStateHeading(__('audit::audit.log.empty.heading'))
            ->emptyStateDescription(__('audit::audit.log.empty.description'));
    }
}
