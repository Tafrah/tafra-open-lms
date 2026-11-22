<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Billing\Domain\Models\Enrollment;
use Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource\Pages;
use Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource\Tables\EnrollmentsTable;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Students->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('billing::billing.enrollment.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('billing::billing.enrollment.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['student:id,name,phone', 'bootcamp:id,name']);
    }

    public static function table(Table $table): Table
    {
        return EnrollmentsTable::configure($table);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnrollments::route('/'),
        ];
    }
}
