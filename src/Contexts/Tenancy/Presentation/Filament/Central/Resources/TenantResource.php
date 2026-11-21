<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Presentation\Filament\Central\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource\Pages;
use Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource\Schemas\TenantForm;
use Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource\Tables\TenantsTable;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

/**
 * إدارة المؤسسات — في اللوحة المركزية بس (docs/24 بند ٣).
 *
 * بتعرض **كل** المؤسسات: فريق التشغيل مش عضو في المؤسسات، والتقييد
 * كله في `TenantPolicy` (المنطقة + الصلاحية على الفريق المركزي).
 */
final class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'slug';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Tenancy->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('tenancy::tenancy.tenant.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tenancy::tenancy.tenant.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['slug', 'domain'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) $record->getAttribute('name');
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
