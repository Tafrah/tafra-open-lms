<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Mcp;

use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentMethodResource;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpResource;
use Src\Support\Presentation\Mcp\RecordOperation;

/**
 * طرق الدفع — قراءة بس.
 */
final class PaymentMethodMcp extends McpResource
{
    private const LABELS = 'billing::billing.method.fields.';

    public static function filamentResource(): string
    {
        return PaymentMethodResource::class;
    }

    public function fields(): array
    {
        return [
            McpField::string('name')->readOnly()->label(self::LABELS.'name'),
            McpField::string('account_number')->readOnly()->label(self::LABELS.'account_number'),
            McpField::boolean('is_active')->readOnly()->filterable()->label(self::LABELS.'is_active'),
        ];
    }

    public function operations(): array
    {
        return [RecordOperation::List, RecordOperation::Show];
    }

    public function with(): array
    {
        return [];
    }
}
