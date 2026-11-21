<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Presentation\Tours\Central;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource\Pages\ListTenants;

final class TenantsListTour extends Tour
{
    private const PREFIX = 'tenancy::tenancy.tours.tenants';

    public static function key(): string
    {
        return 'tenancy.tenants';
    }

    public static function page(): string
    {
        return ListTenants::class;
    }

    public static function panels(): array
    {
        return ['central'];
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'actions', '.fi-ta-ctn'),
            self::step(self::PREFIX, 'create', '.fi-header .fi-ac'),
        ];
    }
}
