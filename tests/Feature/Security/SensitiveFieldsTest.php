<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource\Pages\EditUser;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Infrastructure\Html\HtmlSanitizer;

/*
|--------------------------------------------------------------------------
| الحقول الحساسة: الإخفاء UX — منع الحفظ هو الأمان (docs/20 بند ٢)
|--------------------------------------------------------------------------
*/

it('لا يحفظ الأدوار لمن لا يملك صلاحية إدارتها', function (): void {
    // دور يقدر يعدّل المستخدم (عشان الصفحة تفتح) بس مايقدرش يدير أدواره
    actingWithPermissions([
        'view_any.users', 'view.users', 'update.users',
        'access.panel.admin', 'access.dashboard',
    ]);

    $target = userWithRole('viewer');

    Livewire::test(EditUser::class, ['record' => $target->getKey()])
        ->fillForm(['name' => 'اسم جديد', 'roles' => ['admin']])
        ->call('save');

    expect($target->fresh()->getRoleNames()->all())->not->toContain('admin')
        ->and($target->fresh()->name)->toBe('اسم جديد');
});

it('لا يحفظ is_pinned لمن لا يملك صلاحية التثبيت', function (): void {
    // يقدر ينشر ويعدّل — بس مالوش pin.announcements
    actingWithPermissions([
        'view_any.announcements', 'view.announcements',
        'create.announcements', 'update.announcements', 'publish.announcements',
        'access.panel.admin', 'access.dashboard',
    ]);

    $announcement = Announcement::factory()->published()->create();

    Livewire::test(EditAnnouncement::class, [
        'record' => $announcement->getKey(),
    ])
        ->fillForm([
            'title' => ['ar' => 'عنوان', 'en' => 'Title'],
            'body' => ['ar' => '<p>نص</p>', 'en' => '<p>Body</p>'],
            'is_pinned' => true,
        ])
        ->call('save');

    expect($announcement->fresh()->is_pinned)->toBeFalse();
});

it('يمنع تعيين tenant_id عبر التعبئة الجماعية', function (): void {
    actingAsRole('admin');
    $other = Tenant::factory()->create();

    // في الإنتاج الوضع الصارم مقفول، فالحقل بيتجاهَل بصمت —
    // بنختبر الحالة دي لأنها هي اللي بتحصل فعلاً على السيرفر.
    Model::preventSilentlyDiscardingAttributes(false);

    $announcement = new Announcement;
    $announcement->fill([
        'title' => ['en' => 'x'],
        'body' => ['en' => 'y'],
        'tenant_id' => $other->getKey(),
    ]);

    expect($announcement->tenant_id)->toBeNull();

    Model::preventSilentlyDiscardingAttributes();
});

it('يمنع تعيين حقول الحالة على المستخدم عبر التعبئة الجماعية', function (): void {
    Model::preventSilentlyDiscardingAttributes(false);

    $user = new User;
    $user->fill(['name' => 'x', 'email' => 'x@example.test', 'status' => 'active', 'is_active' => true]);

    expect($user->getAttribute('status'))->toBeNull()
        ->and($user->getAttribute('is_active'))->toBeNull();

    Model::preventSilentlyDiscardingAttributes();
});

it('يرمي استثناء صريح على التعبئة الممنوعة في بيئة التطوير', function (): void {
    expect(fn () => (new Announcement)->fill(['tenant_id' => 1]))
        ->toThrow(MassAssignmentException::class);
});

/*
|--------------------------------------------------------------------------
| XSS المخزّن من المحرّر
|--------------------------------------------------------------------------
*/

it('ينقّي محتوى المحرّر عند الحفظ', function (): void {
    actingAsRole('admin');

    $announcement = Announcement::factory()->create([
        'body' => [
            'ar' => '<p>سليم</p><script>alert(1)</script>',
            'en' => '<p>ok</p><img src=x onerror="alert(1)">',
        ],
    ]);

    $stored = $announcement->fresh();

    expect($stored->getTranslation('body', 'ar'))->not->toContain('<script')
        ->and($stored->getTranslation('body', 'ar'))->toContain('سليم')
        ->and($stored->getTranslation('body', 'en'))->not->toContain('onerror');
});

it('يشيل الوسوم والسمات الخطرة كلها', function (): void {
    $sanitizer = app(HtmlSanitizer::class);

    $dirty = <<<'HTML'
        <p onclick="steal()">نص</p>
        <script>alert(1)</script>
        <iframe src="https://evil.test"></iframe>
        <object data="x"></object>
        <embed src="x">
        <form action="/x"><input name="y"></form>
        <style>body{display:none}</style>
        <a href="javascript:alert(1)">رابط</a>
        HTML;

    $clean = $sanitizer->clean($dirty);

    expect($clean)->not->toContain('<script')
        ->and($clean)->not->toContain('<iframe')
        ->and($clean)->not->toContain('<object')
        ->and($clean)->not->toContain('<embed')
        ->and($clean)->not->toContain('<form')
        ->and($clean)->not->toContain('<style')
        ->and($clean)->not->toContain('onclick')
        ->and($clean)->not->toContain('javascript:')
        ->and($clean)->toContain('نص');
});

it('يضيف rel آمن لكل رابط', function (): void {
    $clean = app(HtmlSanitizer::class)->clean('<a href="https://example.test">رابط</a>');

    expect($clean)->toContain('noopener')
        ->and($clean)->toContain('noreferrer');
});

/*
|--------------------------------------------------------------------------
| الرؤوس الأمنية
|--------------------------------------------------------------------------
*/

it('يرسل الرؤوس الأمنية على كل رد', function (): void {
    $response = $this->get('/admin/login');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('X-Request-Id');
});

it('لا يفرض CSP قبل مرحلة التقرير', function (): void {
    config(['security.csp.enabled' => true, 'security.csp.enforce' => false]);

    $response = $this->get('/admin/login');

    expect($response->headers->has('Content-Security-Policy-Report-Only'))->toBeTrue()
        ->and($response->headers->has('Content-Security-Policy'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| تعداد الحسابات
|--------------------------------------------------------------------------
*/

it('لا يكشف وجود البريد عند فشل الدخول', function (): void {
    $existing = userWithRole('admin');

    $withKnownEmail = $this->post('/admin/login', [
        'email' => $existing->email,
        'password' => 'wrong-password-value',
    ]);

    $withUnknownEmail = $this->post('/admin/login', [
        'email' => 'nobody@futurecode.test',
        'password' => 'wrong-password-value',
    ]);

    expect($withKnownEmail->getStatusCode())->toBe($withUnknownEmail->getStatusCode());
});
