<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Src\Contexts\Content\Domain\Enums\AnnouncementStatus;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource\Pages;
use Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource\Schemas\AnnouncementForm;
use Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource\Tables\AnnouncementsTable;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Content->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('content::content.announcement.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('content::content.announcement.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Cache::remember(
            'nav:draft-announcements:'.(app(TenantContext::class)->id() ?? 'global'),
            now()->addMinutes(2),
            fn (): int => self::getModel()::query()->where('status', AnnouncementStatus::Draft)->count(),
        );

        return $count > 0 ? fc_number($count) : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'gray';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return __('content::content.announcement.badges.drafts');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('author:id,name');
    }

    public static function form(Schema $schema): Schema
    {
        return AnnouncementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnnouncementsTable::configure($table);
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) $record->getAttribute('title');
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('author:id,name');
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'view' => Pages\ViewAnnouncement::route('/{record}'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
