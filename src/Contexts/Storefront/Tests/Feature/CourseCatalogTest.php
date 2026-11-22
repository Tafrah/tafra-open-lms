<?php

declare(strict_types=1);

require_once __DIR__.'/../storefront-helpers.php';

use Src\Contexts\Academy\Domain\Models\EducationalLevel;

beforeEach(fn () => inTenant());

it('الطالب بيشوف كورسات سنته أول ما يفتح، و«كل السنين» بيوريه الباقي', function (): void {
    $first = EducationalLevel::factory()->create(['name' => 'أولى ثانوي']);
    $third = EducationalLevel::factory()->create(['name' => 'تالتة ثانوي']);
    sfCourse(['name' => 'كورس الأولى', 'educational_level_id' => $first->getKey()]);
    sfCourse(['name' => 'كورس التالتة', 'educational_level_id' => $third->getKey()]);

    // زائر: كل الكورسات
    $this->get(route('site.courses'))->assertOk()->assertSee('كورس الأولى')->assertSee('كورس التالتة');

    sfLogin(sfStudent(['educational_level_id' => $third->getKey()]));

    $this->get(route('site.courses'))->assertOk()->assertSee('كورس التالتة')->assertDontSee('كورس الأولى');
    $this->get(route('site.courses', ['level' => 'all']))->assertOk()->assertSee('كورس الأولى')->assertSee('كورس التالتة');
    $this->get(route('site.courses', ['level' => $first->getKey()]))->assertOk()->assertSee('كورس الأولى')->assertDontSee('كورس التالتة');
});
