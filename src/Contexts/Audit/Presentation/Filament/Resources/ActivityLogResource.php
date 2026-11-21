<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Audit\Domain\Models\ActivityLog;
use Src\Contexts\Audit\Presentation\Filament\Resources\ActivityLogResource\Pages;
use Src\Contexts\Audit\Presentation\Filament\Resources\ActivityLogResource\Tables\ActivityLogsTable;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

/**
 * سجل التدقيق — للقراءة فقط. مفيش create/edit/delete، والـ Policy
 * بتمنعهم حتى للمدير العام (append-only).
 */
final class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 80;

    /** `tenant_id` في `properties` مش عمود — الفلترة يدوية */
    protected static bool $isScopedToTenant = false;

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::System->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('audit::audit.log.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('audit::audit.log.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    /** @return Builder<ActivityLog> */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<ActivityLog> $query */
        $query = parent::getEloquentQuery();

        return $query
            ->with('causer:id,name')
            ->forTenant(app(TenantContext::class)->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return ActivityLogsTable::configure($table);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
