<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Billing\Application\DataObjects\RechargeCardBatchData;
use Src\Contexts\Billing\Domain\Enums\RechargeCardType;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\RechargeCard;
use Src\Contexts\Billing\Domain\Models\RechargeCardGroup;
use Src\Contexts\Identity\Domain\Models\User;

/**
 * يولّد دفعة أكواد شحن (أرقام بس، ١٢ رقم — سهلة الكتابة من الكارت المطبوع).
 * الكود فريد على مستوى المنصة، وبيتولّد بـ random_int (CSPRNG) مش rand.
 */
final readonly class GenerateRechargeCardsAction
{
    public const MAX_PER_BATCH = 5000;

    public function handle(RechargeCardBatchData $data, User $actor): RechargeCardGroup
    {
        Gate::forUser($actor)->authorize('create', RechargeCardGroup::class);

        throw_if($data->count < 1 || $data->count > self::MAX_PER_BATCH, BillingException::because('batch_size', ['max' => self::MAX_PER_BATCH]));
        throw_if($data->type === RechargeCardType::Wallet && $data->amount <= 0, BillingException::because('invalid_amount'));
        throw_if($data->type === RechargeCardType::Bootcamp && $data->bootcampId === null, BillingException::because('card_needs_course'));

        return DB::transaction(function () use ($data, $actor): RechargeCardGroup {
            $group = RechargeCardGroup::query()->create([
                'name' => $data->name,
                'type' => $data->type,
                'amount' => $data->amount,
                'bootcamp_id' => $data->bootcampId,
                'cards_count' => $data->count,
                'expiry_date' => $data->expiresAt,
                'is_active' => true,
                'creator_id' => $actor->getKey(),
            ]);

            $codes = $this->uniqueCodes($data->count);
            $now = now();

            foreach (array_chunk($codes, 500) as $chunk) {
                RechargeCard::query()->insert(array_map(fn (string $code): array => [
                    'tenant_id' => $group->tenant_id,
                    'recharge_card_group_id' => $group->getKey(),
                    'code' => $code,
                    'amount' => $data->amount,
                    'currency' => 'EGP',
                    'bootcamp_id' => $data->bootcampId,
                    'is_active' => true,
                    'expiry_date' => $data->expiresAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk));
            }

            return $group;
        });
    }

    /** @return list<string> */
    private function uniqueCodes(int $count): array
    {
        $codes = [];

        while (count($codes) < $count) {
            $batch = [];

            for ($i = count($codes); $i < $count; $i++) {
                $batch[] = self::code();
            }

            $batch = array_values(array_unique($batch));
            $taken = RechargeCard::query()->withoutGlobalScopes()->whereIn('code', $batch)->pluck('code')->all();
            $codes = array_values(array_unique([...$codes, ...array_diff($batch, $taken)]));
        }

        return array_slice($codes, 0, $count);
    }

    public static function code(): string
    {
        $code = (string) random_int(1, 9);

        for ($i = 0; $i < 11; $i++) {
            $code .= (string) random_int(0, 9);
        }

        return $code;
    }
}
