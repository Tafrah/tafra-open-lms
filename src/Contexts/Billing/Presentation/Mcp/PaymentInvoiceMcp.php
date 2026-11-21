<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Mcp;

use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpResource;
use Src\Support\Presentation\Mcp\RecordOperation;

/**
 * الفواتير — قراءة بس. قبول/رفض الإيصال قرار فلوس للمدرس من اللوحة.
 */
final class PaymentInvoiceMcp extends McpResource
{
    private const LABELS = 'billing::billing.invoice.fields.';

    public static function filamentResource(): string
    {
        return PaymentInvoiceResource::class;
    }

    public function fields(): array
    {
        return [
            McpField::string('number')->readOnly()->label(self::LABELS.'number'),
            McpField::number('amount')->readOnly()->label(self::LABELS.'amount'),
            McpField::enum('status', InvoiceStatus::class)->readOnly()->filterable()->label('common.status'),
            McpField::enum('payment_gateway', PaymentGateway::class)->readOnly()->filterable()->label(self::LABELS.'gateway'),
        ];
    }

    public function operations(): array
    {
        return [RecordOperation::List, RecordOperation::Show];
    }

    public function omitted(): array
    {
        return [
            'approve' => 'billing::billing.mcp.omitted.money',
            'reject' => 'billing::billing.mcp.omitted.money',
        ];
    }

    public function with(): array
    {
        return [];
    }
}
