<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Application\Onboarding;

use FutureCode\Onboarding\Contracts\OnboardingStep;
use Src\Contexts\Settings\Domain\Settings\MailSettings;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageMail;

final class ConfigureMailStep implements OnboardingStep
{
    public static function key(): string
    {
        return 'mail';
    }

    public function label(): string
    {
        return __('fc-onboarding::onboarding.steps.mail.label');
    }

    public function description(): string
    {
        return __('fc-onboarding::onboarding.steps.mail.description');
    }

    public function icon(): string
    {
        return 'heroicon-o-envelope';
    }

    /** «مكتملة» = مشغّل حقيقي مش `log`، وبيانات الخادم متكتبة. */
    public function isComplete(): bool
    {
        try {
            $mail = app(MailSettings::class);
        } catch (\Throwable) {
            return false;
        }

        return $mail->driver !== 'log'
            && filled($mail->host)
            && filled($mail->from_address);
    }

    public function isRequired(): bool
    {
        return false;
    }

    public function url(): string
    {
        return ManageMail::getUrl(panel: 'central');
    }

    public function ability(): string
    {
        // نفس صلاحية الصفحة اللي الخطوة بتودّي لها — `access.panel.central` كان بيعرض
        // الخطوة للدعم الفني ورابطها 403
        return 'manage_mail.settings';
    }

    public function panel(): string
    {
        return 'central';
    }

    public function sort(): int
    {
        return 40;
    }
}
