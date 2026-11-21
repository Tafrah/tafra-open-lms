<?php

declare(strict_types=1);

namespace Src\Contexts\Media\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Media\Domain\Models\MediaItem;
use Src\Contexts\Media\Presentation\Filament\Resources\MediaResource\Pages;
use Src\Contexts\Media\Presentation\Filament\Resources\MediaResource\Tables\MediaTable;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class MediaResource extends Resource
{
    protected static ?string $model = MediaItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name';

    /** `tenant_id` في `custom_properties` مش عمود — الفلترة يدوية */
    protected static bool $isScopedToTenant = false;

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Content->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('media::media.item.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('media::media.item.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        $tenantId = app(TenantContext::class)->id();

        return parent::getEloquentQuery()
            ->when($tenantId !== null, fn (Builder $query): Builder => $query
                ->where('custom_properties->tenant_id', $tenantId));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return MediaTable::configure($table);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
        ];
    }
}
