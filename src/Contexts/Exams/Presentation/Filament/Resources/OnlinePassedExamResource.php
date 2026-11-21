<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\Pages;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\RelationManagers;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\Schemas\OnlinePassedExamForm;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\Tables\OnlinePassedExamsTable;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class OnlinePassedExamResource extends Resource
{
    protected static ?string $model = OnlinePassedExam::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Exams->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('exams::exams.attempt.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('exams::exams.attempt.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function getNavigationBadge(): ?string
    {
        if (! self::canViewAny()) {
            return null;
        }

        $count = Cache::remember(
            'nav:attempt:'.(app(TenantContext::class)->id() ?? 'global'),
            now()->addMinute(),
            fn (): int => OnlinePassedExam::query()->where('finished', true)->where('grading_status', GradingStatus::Pending)->count(),
        );

        return $count > 0 ? fc_number($count) : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return __('exams::exams.attempt.badge');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['student:id,name,phone', 'exam:id,name,min_percentage']);
    }

    public static function form(Schema $schema): Schema
    {
        return OnlinePassedExamForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OnlinePassedExamsTable::configure($table);
    }

    /** @return array<int, class-string> */
    public static function getRelations(): array
    {
        return [
            RelationManagers\AnswersRelationManager::class,
        ];
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOnlinePassedExams::route('/'),
            'view' => Pages\ViewOnlinePassedExam::route('/{record}'),
        ];
    }
}
