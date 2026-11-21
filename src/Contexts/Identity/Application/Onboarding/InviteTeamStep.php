<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Application\Onboarding;

use FutureCode\Onboarding\Contracts\OnboardingStep;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource;
use Src\Support\Application\Contracts\TenantContext;

final class InviteTeamStep implements OnboardingStep
{
    public static function key(): string
    {
        return 'team';
    }

    public function label(): string
    {
        return __('fc-onboarding::onboarding.steps.team.label');
    }

    public function description(): string
    {
        return __('fc-onboarding::onboarding.steps.team.description');
    }

    public function icon(): string
    {
        return 'heroicon-o-user-plus';
    }

    /** مستخدم واحد على الأقل غير اللي بيقرا الشاشة. */
    public function isComplete(): bool
    {
        $tenantId = app(TenantContext::class)->id();
        $currentUserId = auth()->id();

        // من غير مستخدم مسجّل مانقدرش نحكم — `whereKeyNot(null)` كان
        // هيعدّ المستخدم نفسه ويقول «مكتملة» بالغلط.
        if ($tenantId === null || $currentUserId === null) {
            return false;
        }

        return User::query()
            ->ofTenant($tenantId)
            ->whereKeyNot($currentUserId)
            ->exists();
    }

    public function isRequired(): bool
    {
        return false;
    }

    public function url(): string
    {
        return UserResource::getUrl('create');
    }

    public function ability(): ?string
    {
        return null;
    }

    public function panel(): string
    {
        return 'admin';
    }

    public function sort(): int
    {
        return 30;
    }
}
