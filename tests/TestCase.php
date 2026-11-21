<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Src\Contexts\Tenancy\Domain\Models\Tenant;

abstract class TestCase extends BaseTestCase
{
    public ?Tenant $tenant = null;

    /**
     * جداول مابتتفرّغش بين الاختبارات في وضع multi (`DatabaseTruncation`).
     *
     * `settings` صفوفه جاية من ميجريشنز spatie/settings نفسها — لو اتفرّغ،
     * أول صفحة بترمي `MissingSettings`. في single مش مستخدمة: `RefreshDatabase`
     * بيرجّع الـ transaction ومابيلمسش صفوف الميجريشنز (docs/17 بند ١٢).
     *
     * @var list<string>
     */
    protected array $exceptTables = ['settings', 'system_state'];

    /**
     * الاختبارات مابتعتمدش على أصول مبنية.
     *
     * تخطيط Filament بينادي `@vite`، واللي بيرمي «Vite manifest not found»
     * لو `public/build/manifest.json` مش موجود. الملف ده ناتج بناء ومتجاهَل
     * في git — يعني موجود على جهاز المطوّر ومش موجود على نسخة نضيفة ولا في
     * الـ CI. من غير `withoutVite()` تسع اختبارات بترندر صفحات بتفشل هناك بس
     * (docs/21 بند ٢٦).
     *
     * ده مش إخفاء لمشكلة: بناء الأصول له مهمة منفصلة في الـ CI بتتأكد إنه
     * بيشتغل، و`deploy.sh` بيبنيه قبل النشر. الاختبارات بتقيس الرندر
     * والصلاحيات، مش خط أنابيب الأصول.
     *
     * الاستثناء الوحيد: اختبارات المتصفح (`BrowserTestCase`) — الجولات
     * والثيم محتاجين الأصول الحقيقية، فبتشتغل بعد `npm run build`.
     */
    protected bool $usesBuiltAssets = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->usesBuiltAssets) {
            $this->withoutVite();
        }
    }
}
