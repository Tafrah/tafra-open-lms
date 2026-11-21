<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource;

final class ListPaymentInvoices extends ListRecords
{
    protected static string $resource = PaymentInvoiceResource::class;
}
