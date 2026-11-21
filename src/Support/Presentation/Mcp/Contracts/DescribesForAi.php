<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp\Contracts;

/**
 * شاشة بتوصف نفسها للذكاء الاصطناعي بنص صريح بدل نص جولتها.
 *
 * اختياري: من غيره `SystemMap` بيبني الوصف من خطوات الجولة (اللي كل شاشة
 * لازم تشحنها أصلاً — قاعدة ٩)، أو من سبب الإعفاء. استخدمه لما الجولة
 * مش كفاية تشرح الشاشة بتعمل إيه.
 */
interface DescribesForAi
{
    /** نص مترجم: الشاشة بتعمل إيه، وإمتى المستخدم يروحلها. */
    public static function aiDescription(): string;
}
