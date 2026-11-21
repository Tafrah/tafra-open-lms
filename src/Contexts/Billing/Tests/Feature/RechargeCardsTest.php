<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Application\Actions\GenerateRechargeCardsAction;
use Src\Contexts\Billing\Application\Actions\RedeemRechargeCardAction;
use Src\Contexts\Billing\Application\DataObjects\RechargeCardBatchData;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Billing\Domain\Enums\RechargeCardType;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\RechargeCard;
use Src\Contexts\Students\Domain\Models\Student;

it('بيولّد دفعة أكواد فريدة من ١٢ رقم', function (): void {
    $admin = userWithRole('admin');

    $group = app(GenerateRechargeCardsAction::class)->handle(
        RechargeCardBatchData::fromArray(['name' => 'سنتر المعادي', 'type' => 'wallet', 'amount' => 100, 'count' => 300]),
        $admin,
    );

    $codes = RechargeCard::query()->where('recharge_card_group_id', $group->getKey())->pluck('code');

    expect($codes)->toHaveCount(300)
        ->and($codes->unique())->toHaveCount(300)
        ->and($codes->every(fn (string $c): bool => preg_match('/^[1-9]\d{11}$/', $c) === 1))->toBeTrue();
});

it('المساعد مايقدرش يولّد أكواد', function (): void {
    $assistant = userWithRole('assistant');

    expect(fn () => app(GenerateRechargeCardsAction::class)->handle(
        RechargeCardBatchData::fromArray(['name' => 'x', 'type' => 'wallet', 'amount' => 50, 'count' => 1]),
        $assistant,
    ))->toThrow(AuthorizationException::class);
});

it('كارت المحفظة بيزوّد الرصيد مرة واحدة بس', function (): void {
    $admin = userWithRole('admin');
    $group = app(GenerateRechargeCardsAction::class)->handle(
        RechargeCardBatchData::fromArray(['name' => 'x', 'type' => 'wallet', 'amount' => 75, 'count' => 1]),
        $admin,
    );
    $card = $group->cards()->first();
    $student = Student::factory()->create();

    // مسافات وشرَط زي ما الطالب ممكن يكتبها من الكارت
    $spaced = implode(' ', str_split($card->code, 4));
    $result = app(RedeemRechargeCardAction::class)->handle($student, $spaced);

    expect($result['kind'])->toBe('wallet')
        ->and((float) $student->fresh()->balance)->toBe(75.0)
        ->and($card->fresh()->used_by)->toBe($student->getKey());

    expect(fn () => app(RedeemRechargeCardAction::class)->handle(Student::factory()->create(), $card->code))
        ->toThrow(BillingException::class);
});

it('كارت المحاضرة بيفتح الكورس، ومابيتحرقش لو الطالب مشترك أصلًا', function (): void {
    $admin = userWithRole('admin');
    $bootcamp = Bootcamp::factory()->create();
    $group = app(GenerateRechargeCardsAction::class)->handle(
        RechargeCardBatchData::fromArray(['name' => 'x', 'type' => RechargeCardType::Bootcamp, 'bootcamp_id' => $bootcamp->getKey(), 'count' => 2]),
        $admin,
    );
    [$first, $second] = $group->cards()->get()->all();
    $student = Student::factory()->create();

    expect(app(RedeemRechargeCardAction::class)->handle($student, $first->code)['kind'])->toBe('bootcamp')
        ->and(app(BootcampAccess::class)->has($student, $bootcamp))->toBeTrue();

    expect(fn () => app(RedeemRechargeCardAction::class)->handle($student, $second->code))->toThrow(BillingException::class);
    expect($second->fresh()->used_at)->toBeNull();
});

it('الكارت المنتهي أو الدفعة الموقوفة مابيشتغلوش', function (): void {
    $admin = userWithRole('admin');
    $group = app(GenerateRechargeCardsAction::class)->handle(
        RechargeCardBatchData::fromArray(['name' => 'x', 'type' => 'wallet', 'amount' => 10, 'count' => 2, 'expiry_date' => now()->addDay()->toDateTimeString()]),
        $admin,
    );
    [$a, $b] = $group->cards()->get()->all();
    $student = Student::factory()->create();

    $this->travel(2)->days();
    expect(fn () => app(RedeemRechargeCardAction::class)->handle($student, $a->code))->toThrow(BillingException::class);

    $this->travelBack();
    $group->update(['is_active' => false]);
    expect(fn () => app(RedeemRechargeCardAction::class)->handle($student, $b->code))->toThrow(BillingException::class);
});
