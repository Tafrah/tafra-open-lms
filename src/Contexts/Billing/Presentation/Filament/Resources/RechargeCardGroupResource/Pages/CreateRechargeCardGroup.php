<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Billing\Application\Actions\GenerateRechargeCardsAction;
use Src\Contexts\Billing\Application\DataObjects\RechargeCardBatchData;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource;
use Src\Contexts\Identity\Domain\Models\User;

final class CreateRechargeCardGroup extends CreateRecord
{
    protected static string $resource = RechargeCardGroupResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();
        throw_unless($user instanceof User, AuthorizationException::class);

        return app(GenerateRechargeCardsAction::class)->handle(RechargeCardBatchData::fromArray($data), $user);
    }
}
