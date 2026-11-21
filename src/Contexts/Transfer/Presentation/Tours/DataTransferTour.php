<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Transfer\Presentation\Filament\Pages\DataTransfer;

final class DataTransferTour extends Tour
{
    private const PREFIX = 'transfer::transfer.tours.page';

    public static function key(): string
    {
        return 'transfer.page';
    }

    public static function page(): string
    {
        return DataTransfer::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'actions', '.fi-header .fi-ac'),
        ];
    }
}
