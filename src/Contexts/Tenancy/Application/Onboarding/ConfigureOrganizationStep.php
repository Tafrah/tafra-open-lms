<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Application\Onboarding;

use Filament\Facades\Filament;
use FutureCode\Onboarding\Contracts\OnboardingStep;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Contexts\Tenancy\Presentation\Filament\Pages\EditOrganization;

final class ConfigureOrganizationStep implements OnboardingStep
{
    public static function key(): string
    {
        return 'organization';
    }

    public function label(): string
    {
        return __('fc-onboarding::onboarding.steps.organization.label');
    }

    public function description(): string
    {
        return __('fc-onboarding::onboarding.steps.organization.description');
    }

    public function icon(): string
    {
        return 'heroicon-o-building-office-2';
    }

    /** بيقرا الحالة الحقيقية: الاسم متكتب بكل اللغات المدعومة؟ */
    public function isComplete(): bool
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Tenant) {
            return false;
        }

        foreach ((array) config('app.supported_locales', []) as $locale) {
            if (blank($tenant->getTranslation('name', $locale, false))) {
                return false;
            }
        }

        return true;
    }

    public function isRequired(): bool
    {
        return true;
    }

    /** ملف المؤسسة جوه لوحتها — شاشة المؤسسات نفسها بقت مركزية (docs/24 بند ٣). */
    public function url(): ?string
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Tenant
            ? EditOrganization::getUrl(tenant: $tenant)
            : null;
    }

    public function ability(): string
    {
        return 'access.organization_profile';
    }

    public function panel(): string
    {
        return 'admin';
    }

    public function sort(): int
    {
        return 10;
    }
}
