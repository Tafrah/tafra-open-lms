<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Tenancy\Presentation\Filament\Pages\EditOrganization;

/** جولة ملف المؤسسة — الطريق الوحيد لمديرها يغيّر اسمها ولونها (docs/24 بند ٣). */
final class OrganizationProfileTour extends Tour
{
    private const PREFIX = 'tenancy::tenancy.tours.organization_profile';

    public static function key(): string
    {
        return 'tenancy.organization_profile';
    }

    public static function page(): string
    {
        return EditOrganization::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'form', '#form'),
            self::step(self::PREFIX, 'save', '#form .fi-sc-actions'),
        ];
    }
}
