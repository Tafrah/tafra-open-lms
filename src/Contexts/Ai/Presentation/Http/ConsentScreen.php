<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Presentation\Http;

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Ai\Application\Services\AiAvailability;
use Src\Contexts\Ai\Domain\Enums\AiAccessLevel;
use Src\Contexts\Ai\Domain\Models\AiConnection;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

/**
 * شاشة الموافقة على ربط تطبيق ذكاء اصطناعي (`Passport::authorizationView`).
 *
 * المستخدم بيختار **المؤسسة** اللي التطبيق هيشتغل جواها و**المستوى**
 * (قراءة / قراءة وتعديل). الاختيارات المعروضة بس هي اللي `connect` بيسمح
 * بيها — والـ Action بيسأل تاني عند الحفظ.
 */
final readonly class ConsentScreen
{
    public function __construct(private AiAvailability $availability) {}

    /** @param array<string, mixed> $parameters من Passport: client, user, scopes, request, authToken */
    public function render(array $parameters): Response
    {
        $user = $parameters['user'] ?? null;

        abort_unless($user instanceof User, 403);

        $targets = $this->targets($user);

        return response()->view('ai::oauth.authorize', [
            ...$parameters,
            'targets' => $targets,
            'levels' => AiAccessLevel::cases(),
            'denied' => $targets === [],
        ]);
    }

    /**
     * المؤسسات اللي المستخدم يقدر يربط عليها — والمركزية لو مسموح.
     *
     * @return list<array{value: string, label: string, writes: bool}>
     */
    private function targets(User $user): array
    {
        $targets = [];

        $tenants = $user->tenants()
            ->where('tenants.is_active', true)
            ->whereNull('tenants.suspended_at')
            ->orderBy('tenants.id')
            ->get();

        foreach ($tenants as $tenant) {
            if (! $tenant instanceof Tenant || ! Gate::forUser($user)->allows('connect', [AiConnection::class, $tenant])) {
                continue;
            }

            $targets[] = [
                'value' => (string) $tenant->getKey(),
                'label' => (string) $tenant->getAttribute('name'),
                'writes' => $this->availability->writesAllowedFor((int) $tenant->getKey()),
            ];
        }

        if (Gate::forUser($user)->allows('connect', [AiConnection::class, null])) {
            $targets[] = ['value' => 'central', 'label' => __('ai::ai.consent.central'), 'writes' => true];
        }

        return $targets;
    }
}
