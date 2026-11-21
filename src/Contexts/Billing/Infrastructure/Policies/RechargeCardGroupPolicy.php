<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Billing\Domain\Models\RechargeCardGroup;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * دفعات أكواد الشحن.
 */
final class RechargeCardGroupPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'recharge_cards';
    }

    public function print(User $user, RechargeCardGroup $record): Response
    {
        return $this->owned($user, $record, 'print')
            ->response();
    }
}
