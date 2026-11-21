<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentMethodResource\Pages\ManagePaymentMethods;

final class PaymentMethodsListTour extends Tour
{
    private const PREFIX = 'billing::billing.tours.method';

    public static function key(): string
    {
        return 'billing.method';
    }

    public static function page(): string
    {
        return ManagePaymentMethods::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'table', '.fi-ta-ctn'),
            self::step(self::PREFIX, 'create', '.fi-header .fi-ac'),
        ];
    }
}
