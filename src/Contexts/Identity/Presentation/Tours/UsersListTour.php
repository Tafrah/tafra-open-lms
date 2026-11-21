<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use FutureCode\Onboarding\Tours\TourStep;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource\Pages\ListUsers;

/** جولة شاشة المستخدمين — ٤ خطوات، وكل خطوة بتحترم الصلاحيات. */
final class UsersListTour extends Tour
{
    public static function key(): string
    {
        return 'users.list';
    }

    public static function page(): string
    {
        return ListUsers::class;
    }

    /** @return array<int, TourStep> */
    public function steps(): array
    {
        return [
            TourStep::make('search')
                ->element('.fi-ta-search-field')
                ->title(__('identity::identity.tours.users.search.title'))
                ->body(__('identity::identity.tours.users.search.body'))
                ->placement('bottom'),

            TourStep::make('filters')
                ->element('.fi-ta-filters')
                ->title(__('identity::identity.tours.users.filters.title'))
                ->body(__('identity::identity.tours.users.filters.body'))
                ->placement('bottom'),

            TourStep::make('columns')
                ->element('.fi-ta-col-manager-dropdown')
                ->title(__('identity::identity.tours.users.columns.title'))
                ->body(__('identity::identity.tours.users.columns.body'))
                ->placement('bottom'),

            TourStep::make('create')
                ->element('.fi-header .fi-ac')
                ->title(__('identity::identity.tours.users.create.title'))
                ->body(__('identity::identity.tours.users.create.body'))
                ->placement('bottom')
                // الخطوة بتتخطى تلقائياً لو مالوش صلاحية الإنشاء
                ->ability('access.dashboard'),
        ];
    }
}
