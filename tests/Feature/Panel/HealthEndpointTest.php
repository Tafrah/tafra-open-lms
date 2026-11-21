<?php

declare(strict_types=1);

it('يقفل نقطة الصحة لو المفتاح مش مضبوط', function (): void {
    config(['security.health_endpoint_secret' => null]);

    $this->get('/health')->assertNotFound();
});

it('يرفض مفتاح غلط بـ 404 مش 403', function (): void {
    config(['security.health_endpoint_secret' => 'right-secret']);

    // 404 مقصودة: 403 بتأكد إن النقطة موجودة
    $this->get('/health?secret=wrong')->assertNotFound();
});

it('يرجّع نتائج الفحص بالمفتاح الصحيح', function (): void {
    config(['security.health_endpoint_secret' => 'right-secret']);

    $this->get('/health?secret=right-secret')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json');
});
