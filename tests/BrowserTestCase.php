<?php

declare(strict_types=1);

namespace Tests;

/**
 * رحلات المستخدم في متصفح حقيقي (docs/17 بند ١٣).
 *
 * الفرق عن `TestCase`: الأصول الحقيقية (`npm run build`) — الجولات مبنية
 * على driver.js المحزوم، والثيم CSS مبني. من غيرها الاختبار بيعدّي على صفحة
 * مالهاش شكل ولا جولة، ويبقى بيكدب.
 */
abstract class BrowserTestCase extends TestCase
{
    protected bool $usesBuiltAssets = true;
}
