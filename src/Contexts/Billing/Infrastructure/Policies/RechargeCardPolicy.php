<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * أكواد الشحن. الكود المستخدم مايتعدّلش ولا يتحذف.
 */
final class RechargeCardPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'recharge_cards';
    }

    public function update(User $user, Model $record): Response
    {
        return $this->owned($user, $record, 'update')
            ->rule($record->getAttribute('used_at') === null, 'recharge_card.used')
            ->response();
    }

    public function delete(User $user, Model $record): Response
    {
        return $this->owned($user, $record, 'delete')
            ->rule($record->getAttribute('used_at') === null, 'recharge_card.used')
            ->response();
    }
}
