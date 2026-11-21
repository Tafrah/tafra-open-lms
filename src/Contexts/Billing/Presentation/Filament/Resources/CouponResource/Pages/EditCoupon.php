<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource;

final class EditCoupon extends EditRecord
{
    protected static string $resource = CouponResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
