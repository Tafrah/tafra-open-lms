<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Application\Onboarding;

use FutureCode\Onboarding\Contracts\OnboardingStep;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource;

/** معالج اللوحة المركزية: من غير مؤسسة واحدة المنصة مالهاش لازمة (docs/24 بند ٣). */
final class CreateFirstTenantStep implements OnboardingStep
{
    public static function key(): string
    {
        return 'first_tenant';
    }

    public function label(): string
    {
        return __('tenancy::tenancy.onboarding.first_tenant.label');
    }

    public function description(): string
    {
        return __('tenancy::tenancy.onboarding.first_tenant.description');
    }

    public function icon(): string
    {
        return 'heroicon-o-building-office-2';
    }

    public function isComplete(): bool
    {
        return Tenant::query()->exists();
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function url(): string
    {
        return TenantResource::getUrl('create', panel: 'central');
    }

    public function ability(): string
    {
        // الخطوة بتودّي لصفحة الإنشاء — `access.tenancy` لوحده كان بيعرضها للدعم
        // الفني (عنده المنطقة ومالوش الإنشاء) فتفضل الخطوة الإجبارية مقفولة قدامه
        return 'create.tenants';
    }

    public function panel(): string
    {
        return 'central';
    }

    public function sort(): int
    {
        return 10;
    }
}
