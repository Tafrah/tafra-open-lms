<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Mcp;

use Src\Contexts\Billing\Domain\Enums\CouponType;
use Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource;
use Src\Support\Presentation\Mcp\McpField;
use Src\Support\Presentation\Mcp\McpResource;
use Src\Support\Presentation\Mcp\RecordOperation;

/**
 * الكوبونات — قراءة بس.
 */
final class CouponMcp extends McpResource
{
    private const LABELS = 'billing::billing.coupon.fields.';

    public static function filamentResource(): string
    {
        return CouponResource::class;
    }

    public function fields(): array
    {
        return [
            McpField::string('code')->readOnly()->label(self::LABELS.'code'),
            McpField::enum('type', CouponType::class)->readOnly()->label(self::LABELS.'type'),
            McpField::number('value')->readOnly()->label(self::LABELS.'value'),
            McpField::integer('current_redemptions')->readOnly()->label(self::LABELS.'redemptions'),
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
