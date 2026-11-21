<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Application\Queries;

use Carbon\CarbonInterface;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Domain\Models\User;

/**
 * الواجهة العامة لسياق الهوية.
 *
 * السياقات التانية بتسأل من هنا بدل ما تستورد `User` — وده أحد
 * الطريقتين المسموح بيهم للتواصل بين السياقات (docs/01).
 */
final readonly class TenantUserSummary
{
    /** @return array{total: int, joined: int, invited: int} */
    public function forTenant(int $tenantId, CarbonInterface $since): array
    {
        $base = User::query()->ofTenant($tenantId);

        return [
            'total' => (clone $base)->count(),
            'joined' => (clone $base)->where('users.created_at', '>=', $since)->count(),
            'invited' => (clone $base)->where('status', UserStatus::Invited)->count(),
        ];
    }
}
