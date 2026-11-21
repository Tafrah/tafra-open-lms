<?php

declare(strict_types=1);

namespace FutureCode\Onboarding;

use Filament\Contracts\Plugin;
use Filament\Panel;
use FutureCode\Onboarding\Pages\OnboardingAnalytics;
use FutureCode\Onboarding\Pages\SetupWizard;
use FutureCode\Onboarding\Widgets\OnboardingChecklistWidget;

/**
 * نقطة التركيب الوحيدة للباكدج في أي لوحة Filament.
 *
 *     ->plugin(FilamentOnboardingPlugin::make())
 *
 * الباكدج ده الهدف منه إنه يتحط في أي مشروع (docs/15 بند ٢). من غير
 * الكلاس ده، كل مشروع لازم يعرف بنفسه إن فيه صفحتين وودجت لازم
 * يسجّلهم بالاسم — وده بالظبط اللي الباكدج المفروض يخلّص منه.
 *
 * كل حاجة بتحترم `config/fc-onboarding.php`: لو المعالج أو المقاييس
 * أو قائمة المهام متقفولة، مابتتسجّلش أصلاً.
 */
final class FilamentOnboardingPlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'fc-onboarding';
    }

    public function register(Panel $panel): void
    {
        if (! config('fc-onboarding.enabled', true)) {
            return;
        }

        $pages = [];

        if (config('fc-onboarding.wizard.enabled', true)) {
            $pages[] = SetupWizard::class;
        }

        if (config('fc-onboarding.analytics.enabled', true)) {
            $pages[] = OnboardingAnalytics::class;
        }

        if ($pages !== []) {
            $panel->pages($pages);
        }

        if (config('fc-onboarding.checklist.enabled', true)) {
            $panel->widgets([OnboardingChecklistWidget::class]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
