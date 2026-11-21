<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake((string) config('installer.public_files.disk'));
});

it('بيقدّم ملفات الديسك العام من /uploads لما مفيش symlink', function (): void {
    Storage::disk((string) config('installer.public_files.disk'))->put('1/avatar.png', 'png-bytes');

    $response = $this->get('/uploads/1/avatar.png');

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Content-Security-Policy'))->toContain('sandbox')
        ->and($response->headers->get('Cache-Control'))->toContain('public');
});

it('ملف مش موجود 404', function (): void {
    $this->get('/uploads/missing.png')->assertNotFound();
});

it('الملفات المخفية والصعود في المسار ممنوعين', function (string $path): void {
    Storage::disk((string) config('installer.public_files.disk'))->put('.htaccess', 'deny');
    Storage::disk((string) config('installer.public_files.disk'))->put('1/.secret', 'x');

    $this->get('/uploads/'.$path)->assertNotFound();
})->with(['.htaccess', '1/.secret', '../private/x', '1/../../.env']);
