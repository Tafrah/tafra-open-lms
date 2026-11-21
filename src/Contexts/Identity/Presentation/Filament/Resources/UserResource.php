<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource\Pages;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource\Schemas\UserForm;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource\Tables\UsersTable;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

/**
 * المورد النموذجي — كل الـ patterns المطلوبة موجودة هنا.
 *
 * لاحظ: **مفيش** `canViewAny()` ولا `canCreate()` — Filament بينادي
 * الـ Policy لوحده. الاستثناء الوحيد هو التنقّل (docs/02 بند ٦).
 */
final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    /** المستخدم بينتمي لأكتر من مؤسسة — العلاقة many-to-many */
    protected static ?string $tenantOwnershipRelationshipName = 'tenants';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Identity->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('identity::identity.user.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('identity::identity.user.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();     // ← بتمرّ على الـ Policy، مش على نص صلاحية
    }

    public static function getNavigationBadge(): ?string
    {
        // البادچ بيتحسب في كل طلب لكل مورد ظاهر — الكاش إلزامي (docs/07 بند ٤)
        $count = Cache::remember(
            'nav:invited-users:'.(app(TenantContext::class)->id() ?? 'global'),
            now()->addMinutes(2),
            fn (): int => self::getModel()::query()->where('status', UserStatus::Invited)->count(),
        );

        return $count > 0 ? fc_number($count) : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return __('identity::identity.user.badges.invited');
    }

    public static function getEloquentQuery(): Builder
    {
        // `tenants:id` مقصودة: UserPolicy بتسأل عن عضوية المؤسسة لكل صف،
        // ومن غير التحميل المسبق ده بيبقى استعلام لكل سجل في الجدول.
        return parent::getEloquentQuery()->with(['roles:id,name', 'tenants:id']);
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'code'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) $record->getAttribute('name');
    }

    /** @return array<string, string> */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('identity::identity.user.fields.email') => (string) $record->getAttribute('email'),
            __('identity::identity.user.fields.roles') => $record->getRelationValue('roles')->pluck('name')
                ->map(static fn (string $name): string => __("authorization.roles.{$name}"))
                ->join('، '),
        ];
    }

    /** تجنّب N+1 في نتائج البحث الشامل */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['roles:id,name']);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
