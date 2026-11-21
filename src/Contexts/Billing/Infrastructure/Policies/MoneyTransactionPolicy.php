<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Infrastructure\Policies;

use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * حركات المحفظة — للقراءة بس؛ التعديل بيتم بحركة جديدة.
 */
final class MoneyTransactionPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'money_transactions';
    }
}
