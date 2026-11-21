<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\DataObjects;

use Carbon\CarbonImmutable;
use Src\Contexts\Billing\Domain\Enums\RechargeCardType;

final readonly class RechargeCardBatchData
{
    public function __construct(
        public string $name,
        public RechargeCardType $type,
        public int $count,
        public float $amount = 0.0,
        public ?int $bootcampId = null,
        public ?CarbonImmutable $expiresAt = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $type = $data['type'] instanceof RechargeCardType ? $data['type'] : RechargeCardType::from((string) $data['type']);

        return new self(
            name: (string) $data['name'],
            type: $type,
            count: (int) $data['count'],
            amount: $type === RechargeCardType::Wallet ? (float) ($data['amount'] ?? 0) : 0.0,
            bootcampId: $type === RechargeCardType::Bootcamp ? (int) $data['bootcamp_id'] : null,
            expiresAt: filled($data['expiry_date'] ?? null) ? CarbonImmutable::parse((string) $data['expiry_date']) : null,
        );
    }
}
