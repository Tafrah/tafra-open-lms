<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Application\Onboarding;

use FutureCode\Onboarding\Contracts\OnboardingStep;
use Src\Contexts\Settings\Domain\Settings\StorageSettings;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageStorage;

final class ConfigureStorageStep implements OnboardingStep
{
    public static function key(): string
    {
        return 'storage';
    }

    public function label(): string
    {
        return __('fc-onboarding::onboarding.steps.storage.label');
    }

    public function description(): string
    {
        return __('fc-onboarding::onboarding.steps.storage.description');
    }

    public function icon(): string
    {
        return 'heroicon-o-server-stack';
    }

    /** الملفات الخاصة على نفس ديسك العام = الإعداد لسه مش مضبوط. */
    public function isComplete(): bool
    {
        try {
            $storage = app(StorageSettings::class);
        } catch (\Throwable) {
            return false;
        }

        return filled($storage->default_disk)
            && filled($storage->private_disk)
            && $storage->private_disk !== $storage->default_disk
            && $storage->private_collections !== [];
    }

    public function isRequired(): bool
    {
        return false;
    }

    public function url(): string
    {
        return ManageStorage::getUrl(panel: 'central');
    }

    public function ability(): string
    {
        // نفس صلاحية الصفحة اللي الخطوة بتودّي لها — `access.panel.central` كان بيعرض
        // الخطوة للدعم الفني ورابطها 403
        return 'manage_storage.settings';
    }

    public function panel(): string
    {
        return 'central';
    }

    public function sort(): int
    {
        return 50;
    }
}
