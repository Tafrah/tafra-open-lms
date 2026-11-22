<?php

declare(strict_types=1);

require_once __DIR__.'/../helpers.php';

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Src\Contexts\Installer\Infrastructure\Updates\ReleaseChecker;

beforeEach(fn () => File::delete(ReleaseChecker::path()));
afterEach(fn () => File::delete(ReleaseChecker::path()));

/** @param array<string, string> $release */
function storeRelease(array $release): void
{
    File::put(ReleaseChecker::path(), (string) json_encode($release));
}

function onVersion(string $version): void
{
    $file = sys_get_temp_dir().'/lms-version-'.bin2hex(random_bytes(4));
    File::put($file, $version);
    config(['installer.version_file' => $file]);
}

it('الفحص بيسجّل آخر إصدار من GitHub والشريط بيظهر لو أحدث', function (): void {
    onVersion('1.0.0');
    Http::fake(['api.github.com/*' => Http::response(['tag_name' => 'v1.2.0', 'html_url' => 'https://github.com/Tafrah/tafra-open-lms/releases/tag/v1.2.0'])]);

    $this->artisan('lms:check-update')->assertSuccessful();

    expect(app(ReleaseChecker::class)->newerRelease())->toMatchArray(['version' => '1.2.0']);

    Http::assertSent(fn ($request): bool => $request->method() === 'GET' && $request->body() === '');
});

it('نفس الإصدار أو أقدم = مفيش شريط', function (): void {
    onVersion('1.2.0');
    storeRelease(['version' => '1.2.0', 'url' => '', 'checked_at' => now()->toIso8601String()]);
    expect(app(ReleaseChecker::class)->newerRelease())->toBeNull();

    storeRelease(['version' => '1.1.9', 'url' => '', 'checked_at' => now()->toIso8601String()]);
    expect(app(ReleaseChecker::class)->newerRelease())->toBeNull();
});

it('LMS_UPDATE_CHECK=false بيقفل الفحص تماماً', function (): void {
    config(['installer.updates.enabled' => false]);
    Http::fake();

    $this->artisan('lms:check-update')->assertSuccessful();

    Http::assertNothingSent();
    expect(app(ReleaseChecker::class)->newerRelease())->toBeNull();
});

it('GitHub واقع = الفحص مابيكسرش حاجة', function (): void {
    Http::fake(['api.github.com/*' => Http::response('down', 503)]);

    $this->artisan('lms:check-update')->assertSuccessful();
    expect(is_file(ReleaseChecker::path()))->toBeFalse();
});

it('الشريط بيظهر للمدير على الرئيسية', function (): void {
    onVersion('1.0.0');
    storeRelease(['version' => '1.3.0', 'url' => 'https://example.test/r', 'checked_at' => now()->toIso8601String()]);

    actingAsRole('admin');

    $this->get('/admin/t/'.currentTenant()->slug)
        ->assertOk()
        ->assertSee(__('installer::installer.update.available.title', ['version' => '1.3.0']));
});
