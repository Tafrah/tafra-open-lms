<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\PaymentMethodResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentMethodResource;

final class ManagePaymentMethods extends ManageRecords
{
    protected static string $resource = PaymentMethodResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
