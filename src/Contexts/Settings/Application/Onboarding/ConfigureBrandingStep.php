<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Application\Onboarding;

use FutureCode\Onboarding\Contracts\OnboardingStep;
use Src\Contexts\Settings\Domain\Settings\AppearanceSettings;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageAppearance;
use Src\Support\Infrastructure\Theming\ThemeColorResolver;

final class ConfigureBrandingStep implements OnboardingStep
{
    public static function key(): string
    {
        return 'branding';
    }

    public function label(): string
    {
        return __('fc-onboarding::onboarding.steps.branding.label');
    }

    public function description(): string
    {
        return __('fc-onboarding::onboarding.steps.branding.description');
    }

    public function icon(): string
    {
        return 'heroicon-o-paint-brush';
    }

    public function isComplete(): bool
    {
        try {
            $appearance = app(AppearanceSettings::class);
        } catch (\Throwable) {
            return false;
        }

        return filled($appearance->logo_light_path)
            || $appearance->primary_color !== ThemeColorResolver::DEFAULT_BRAND;
    }

    public function isRequired(): bool
    {
        return false;
    }

    public function url(): string
    {
        return ManageAppearance::getUrl(panel: 'central');
    }

    public function ability(): string
    {
        // نفس صلاحية الصفحة اللي الخطوة بتودّي لها — `access.panel.central` كان بيعرض
        // الخطوة للدعم الفني ورابطها 403
        return 'manage_appearance.settings';
    }

    public function panel(): string
    {
        return 'central';
    }

    public function sort(): int
    {
        return 20;
    }
}
