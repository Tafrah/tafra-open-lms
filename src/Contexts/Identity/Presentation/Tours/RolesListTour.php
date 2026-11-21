<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource\Pages\ListRoles;

final class RolesListTour extends Tour
{
    private const PREFIX = 'identity::identity.tours.roles';

    public static function key(): string
    {
        return 'roles.list';
    }

    public static function page(): string
    {
        return ListRoles::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'table', '.fi-ta-ctn'),
            self::step(self::PREFIX, 'create', '.fi-header .fi-ac'),
        ];
    }
}
