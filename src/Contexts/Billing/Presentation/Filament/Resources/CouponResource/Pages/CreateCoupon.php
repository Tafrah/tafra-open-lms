<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource;

final class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'created_by' => auth()->id()];
    }
}
