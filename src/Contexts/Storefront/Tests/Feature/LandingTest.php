<?php

declare(strict_types=1);

require_once __DIR__.'/../storefront-helpers.php';

use Illuminate\Support\Facades\DB;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Domain\Models\EducationalLevel;

it('الصفحة الرئيسية بتتعرض بإعدادات الموقع وشارة طفرة', function (): void {
    inTenant();
    sfSiteSettings([
        'platform_name' => 'Elmogy Chemistry',
        'teacher_name' => 'Mr. Elmogy',
        'subject' => 'Chemistry',
        'primary_color' => '#AA3377',
        'hero_chip_1' => 'Chip One',
        'facebook_url' => 'https://facebook.com/elmogy',
        'whatsapp_number' => '01012345678',
    ]);
    $level = EducationalLevel::factory()->create(['name' => 'Third secondary']);
    sfCourse(['name' => 'Organic chemistry', 'educational_level_id' => $level->getKey()]);
    Bootcamp::factory()->draft()->create(['name' => 'Hidden draft']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Elmogy Chemistry')
        ->assertSee('Mr. Elmogy')
        ->assertSee('Chip One')
        ->assertSee('--color-primary: #AA3377', escape: false)
        ->assertSee('Organic chemistry')
        ->assertSee('Third secondary')
        ->assertDontSee('Hidden draft')
        ->assertSee('https://facebook.com/elmogy', escape: false)
        ->assertSee('https://wa.me/201012345678', escape: false)
        ->assertSee('data-tafra-credit', escape: false)
        ->assertSee(config('storefront.tafra_url'), escape: false)
        ->assertSee('lang="ar"', escape: false)
        ->assertSee('dir="rtl"', escape: false);
});

it('لون غلط في الإعدادات مايوصلش للـ CSS', function (): void {
    inTenant();
    sfSiteSettings(['primary_color' => 'red;}</style><script>alert(1)</script>']);

    $this->get('/')
        ->assertOk()
        ->assertSee('--color-primary: '.config('storefront.defaults.primary_color'), escape: false)
        ->assertDontSee('<script>alert(1)</script>', escape: false);
});

it('أرقام الصفحة الرئيسية متخزّنة ومش بتتحسب كل طلب', function (): void {
    inTenant();
    sfSiteSettings(['show_stats' => true]);
    sfCourse();

    $this->get('/')->assertOk();

    DB::enableQueryLog();
    $this->get('/')->assertOk();
    $counts = collect(DB::getQueryLog())->filter(fn (array $q): bool => str_contains($q['query'], 'count(*)') && str_contains($q['query'], 'from `students`'));
    DB::disableQueryLog();

    expect($counts)->toBeEmpty();
});

it('من غير مؤسسة الموقع بيقول إنه لسه بيتجهّز', function (): void {
    $this->get('/')->assertStatus(503)->assertSee(__('storefront::storefront.no_academy.title'));
});

it('تبديل اللغة بيقلب الصفحة إنجليزي LTR', function (): void {
    inTenant();

    $this->post(route('site.locale'), ['locale' => 'en'])->assertRedirect();

    $this->get('/')
        ->assertOk()
        ->assertSee('lang="en"', escape: false)
        ->assertSee('dir="ltr"', escape: false)
        ->assertSee(__('storefront::storefront.credit.developed_by', [], 'en'));
});

it('لغة مش مدعومة بترفض', function (): void {
    inTenant();

    $this->post(route('site.locale'), ['locale' => 'fr'])->assertSessionHasErrors('locale');
});

it('صفحة الكورسات بتفلتر بالصف وبالبحث', function (): void {
    inTenant();
    [$first, $second] = EducationalLevel::factory()->count(2)->create();
    sfCourse(['name' => 'Physics basics', 'educational_level_id' => $first->getKey()]);
    sfCourse(['name' => 'Algebra deep dive', 'educational_level_id' => $second->getKey()]);

    $this->get(route('site.courses', ['level' => $first->getKey()]))
        ->assertOk()->assertSee('Physics basics')->assertDontSee('Algebra deep dive');

    $this->get(route('site.courses', ['q' => 'Algebra']))
        ->assertOk()->assertSee('Algebra deep dive')->assertDontSee('Physics basics');

    // نص البحث مايتفسرش كـ wildcard
    $this->get(route('site.courses', ['q' => '%']))->assertOk()->assertDontSee('Physics basics');
});
