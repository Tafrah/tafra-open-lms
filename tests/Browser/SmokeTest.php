<?php

declare(strict_types=1);

it('بيفتح لوحة المؤسسة في متصفح حقيقي بجودة واجهة سليمة', function (): void {
    actingAsRole('admin');

    $page = visit('/admin/t/'.currentTenant()->slug);

    $page->assertSee((string) currentTenant()->name);

    assertUiQuality($page);
});
