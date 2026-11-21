<?php

declare(strict_types=1);

require_once __DIR__.'/../storefront-helpers.php';

use Livewire\Livewire;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Billing\Domain\Enums\RechargeCardType;
use Src\Contexts\Billing\Domain\Models\MoneyTransaction;
use Src\Contexts\Billing\Domain\Models\RechargeCard;
use Src\Contexts\Billing\Domain\Models\RechargeCardGroup;
use Src\Contexts\Storefront\Presentation\Livewire\RedeemCode;
use Src\Contexts\Storefront\StorefrontServiceProvider;

beforeEach(fn () => inTenant());

/** كروت أكوادها أرقام زي `GenerateRechargeCardsAction` (الفاكتوري بيعمل حروف — مابتتشحنش) */
function sfCard(float $amount = 100, ?int $bootcampId = null): RechargeCard
{
    $group = RechargeCardGroup::factory()->create([
        'type' => $bootcampId ? RechargeCardType::Bootcamp : RechargeCardType::Wallet,
        'bootcamp_id' => $bootcampId,
    ]);

    return RechargeCard::factory()->create([
        'recharge_card_group_id' => $group->getKey(),
        'code' => (string) random_int(100000000000, 999999999999),
        'amount' => $amount,
        'bootcamp_id' => $bootcampId,
    ]);
}

it('شحن كود محفظة عبر HTTP بيزوّد الرصيد ويظهر في الحركات', function (): void {
    $student = sfLogin();
    $card = sfCard(75);

    $this->post(route('site.wallet.redeem'), ['code' => implode(' ', str_split($card->code, 4))])
        ->assertRedirect(route('site.wallet'))
        ->assertSessionHas('status');

    expect((float) $student->fresh()->balance)->toBe(75.0)
        ->and(MoneyTransaction::query()->where('student_id', $student->getKey())->count())->toBe(1);

    $this->get(route('site.wallet'))->assertOk()->assertSee(fc_currency(75.0));
});

it('كود كورس بيفتح الكورس ويودّي للمشغّل', function (): void {
    $course = sfCourse();
    $student = sfLogin();
    $card = sfCard(0, $course->getKey());

    $this->post(route('site.wallet.redeem'), ['code' => $card->code])
        ->assertRedirect(route('site.learn.course', $course->slug));

    expect(app(BootcampAccess::class)->has($student, $course))->toBeTrue();
});

it('بعد ٥ أكواد غلط في ١٠ دقايق الشحن بيتقفل حتى للكود الصح', function (): void {
    $student = sfLogin();
    $card = sfCard(50);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('site.wallet.redeem'), ['code' => '11112222333'.$i])->assertSessionHasErrors('code');
    }

    $this->post(route('site.wallet.redeem'), ['code' => $card->code])->assertSessionHasErrors('code');
    expect(session('errors')->first('code'))->toStartWith(explode(':seconds', __('billing::billing.errors.too_many_attempts'))[0]);

    expect((float) $student->fresh()->balance)->toBe(0.0);

    $this->travel(11)->minutes();

    $this->post(route('site.wallet.redeem'), ['code' => $card->code])->assertSessionHasNoErrors();
    expect((float) $student->fresh()->balance)->toBe(50.0);
});

it('الحد لكل طالب — طالب تاني مايتأثرش', function (): void {
    sfLogin();
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('site.wallet.redeem'), ['code' => '99998888777'.$i]);
    }

    $other = sfLogin(sfStudent());
    $card = sfCard(20);

    $this->post(route('site.wallet.redeem'), ['code' => $card->code])->assertSessionHasNoErrors();
    expect((float) $other->fresh()->balance)->toBe(20.0);
});

it('مودال الشحن (Livewire) بيوري نجاح وغلط', function (): void {
    $student = sfLogin();
    $card = sfCard(30);

    Livewire::test(RedeemCode::class)
        ->set('code', '000000000000')
        ->call('redeem')
        ->assertSet('result', 'error')
        ->assertSee(__('billing::billing.errors.card_invalid'))
        ->call('again')
        ->set('code', $card->code)
        ->call('redeem')
        ->assertSet('result', 'success')
        ->assertDispatched('wallet-updated');

    expect((float) $student->fresh()->balance)->toBe(30.0);
});

it('مكوّنات الموقع مابتعيدش ستاك web في طلبات Livewire (الجلسة مابتتقلبش)', function (): void {
    sfLogin();

    $component = Livewire::test(RedeemCode::class);

    expect($component->snapshot['memo']['path'])->toBe(StorefrontServiceProvider::LIVEWIRE_CONTEXT_PATH)
        ->and(app('router')->getRoutes()->getByName('site.livewire-context')->gatherMiddleware())->toBe([]);
});
