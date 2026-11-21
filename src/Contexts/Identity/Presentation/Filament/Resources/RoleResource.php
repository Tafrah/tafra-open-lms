<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource\Pages;
use Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource\Schemas\RoleForm;
use Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource\Tables\RolesTable;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Authorization\CentralRoles;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

/**
 * شاشة الأدوار مكتوبة بإيدنا مش من Shield.
 *
 * السبب: الصلاحيات عندنا مصدرها `config/authorization.php`، و Shield
 * مبني على توليدها من مسح الموارد. كتابتها بنفسنا (≈١٥٠ سطر) بتخلّينا
 * مالكين الشاشة بالكامل — وده القرار اللي docs/02 بند ٨ سايبه مفتوح.
 */
final class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    /** الأدوار عامة — تعريف واحد لكل التثبيت، والربط هو اللي بيتحدد بالمؤسسة */
    protected static bool $isScopedToTenant = false;

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Identity->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('identity::identity.role.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('identity::identity.role.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        // أدوار فريق التشغيل بتتدار من اللوحة المركزية بس (docs/24 بند ٢)
        return parent::getEloquentQuery()
            ->whereNotIn('name', CentralRoles::catalog())
            // الأدوار العامة + أدوار المؤسسة دي بس — `Role::create()` بيختم فريق المؤسسة
            // اللي أنشأته، والقائمة من غير الشرط ده كانت بتعرض أدوار كل المؤسسات
            ->where(fn (Builder $query) => $query
                ->whereNull('roles.tenant_id')
                ->orWhere('roles.tenant_id', app(TenantContext::class)->id()))
            ->withCount(['permissions', 'users']);
    }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
