<?php

declare(strict_types=1);

use Src\Contexts\Audit\Domain\Models\ActivityLog;
use Src\Contexts\Content\Domain\Enums\AnnouncementStatus;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Presentation\Mcp\FcMcpServer;

/*
|--------------------------------------------------------------------------
| أدوات MCP — نفس اللوحة بالظبط، مش باب خلفي (docs/23 بند ٧)
|--------------------------------------------------------------------------
| كل اختبار هنا بيعدّي على الخادم الحقيقي (tools/list و tools/call)، مش على
| الأداة لوحدها — عشان `shouldRegister()` والـ Policies يتفحصوا مع بعض.
*/

function draftAnnouncement(array $attributes = []): Announcement
{
    return Announcement::factory()->create([
        'status' => AnnouncementStatus::Draft,
        'published_at' => null,
        ...$attributes,
    ]);
}

// ══════════════ قائمة الأدوات = صلاحيات الدور ══════════════

it('المشاهد بيشوف أدوات القراءة بس', function (): void {
    actingAsMcp('viewer');

    $tools = mcpToolNames();

    expect($tools)->toContain('describe_system', 'get_screen', 'announcements_list', 'announcements_get')
        ->not->toContain('announcements_create', 'announcements_update', 'announcements_delete', 'announcements_publish');
});

it('المحرر بيشوف الإنشاء والتعديل ومابيشوفش النشر ولا الحذف', function (): void {
    actingAsMcp('editor');

    expect(mcpToolNames())
        ->toContain('announcements_create', 'announcements_update')
        ->not->toContain('announcements_publish', 'announcements_delete', 'announcements_force_delete');
});

it('المدير بيشوف كل أدوات الإعلانات', function (): void {
    actingAsMcp('admin');

    expect(mcpToolNames())->toContain(
        'announcements_list', 'announcements_get', 'announcements_create', 'announcements_update',
        'announcements_delete', 'announcements_restore', 'announcements_force_delete',
        'announcements_publish', 'announcements_pin',
    );
});

it('ربط القراءة مابيشوفش أي أداة كتابة حتى للمدير', function (): void {
    actingAsMcp('admin', write: false);

    $writes = array_filter(mcpToolNames(), static fn (string $name): bool => (bool) preg_match('/_(create|update|delete|restore|force_delete|publish|pin)$/', $name));

    expect($writes)->toBeEmpty();
});

it('الأداة المخفية بترجع «مش موجودة» حتى لو اتنادت بالاسم', function (): void {
    actingAsMcp('admin', write: false);

    callMcp('announcements_create', ['title' => ['ar' => 'x', 'en' => 'x'], 'body' => ['ar' => 'x', 'en' => 'x'], 'severity' => 'info'])
        ->assertHasErrors();

    expect(Announcement::query()->count())->toBe(0);
});

it('مفيش أداة من الممنوع في أي دور', function (): void {
    actingAsMcp('admin');

    $forbidden = array_filter(mcpToolNames(), static fn (string $name): bool => (bool) preg_match('/(impersonate|manage_roles|reset_password|force_logout|export|prune|change_disk|bulk)/', $name));

    expect($forbidden)->toBeEmpty()
        ->and(mcpToolNames())->not->toContain('users_create', 'users_update', 'users_delete', 'roles_create', 'roles_update', 'roles_delete');
});

it('مفيش tenant_id في أي schema', function (): void {
    actingAsMcp('admin');

    $list = FcMcpServer::actingAs(auth()->user())->tools();
    $schemas = json_encode(array_column((fn (): array => $this->items)->call($list), 'inputSchema'));

    expect($schemas)->not->toContain('tenant_id');
});

// ══════════════ القراءة ══════════════

it('القائمة بترجّع إعلانات المؤسسة دي بس', function (): void {
    actingAsMcp('viewer');
    draftAnnouncement(['title' => ['ar' => 'عندنا', 'en' => 'Ours']]);

    $other = Tenant::factory()->create();
    app(TenantContext::class)->forTenant($other->getKey(), fn () => draftAnnouncement(['title' => ['ar' => 'عندهم', 'en' => 'Theirs']]));

    $result = mcpResult(callMcp('announcements_list')->assertOk());

    expect($result['items'])->toHaveCount(1)
        ->and($result['items'][0]['title']['en'])->toBe('Ours');
});

it('سجل من مؤسسة تانية «مش موجود»', function (): void {
    $other = Tenant::factory()->create();
    $theirs = app(TenantContext::class)->forTenant($other->getKey(), fn () => draftAnnouncement());

    actingAsMcp('admin');

    $response = callMcp('announcements_get', ['id' => $theirs->getKey()])->assertHasErrors();

    expect(mcpError($response))->toBe(__('mcp.errors.not_found'));
});

it('البحث والفلتر والصفحات شغالين', function (): void {
    actingAsMcp('viewer');

    draftAnnouncement(['title' => ['ar' => 'صيانة', 'en' => 'Maintenance window']]);
    draftAnnouncement(['title' => ['ar' => 'إجازة', 'en' => 'Holiday']]);
    Announcement::factory()->count(3)->create(['status' => AnnouncementStatus::Published]);

    expect(mcpResult(callMcp('announcements_list', ['search' => 'maint']))['items'])->toHaveCount(1)
        ->and(mcpResult(callMcp('announcements_list', ['filters' => ['status' => 'draft']]))['items'])->toHaveCount(2);

    $first = mcpResult(callMcp('announcements_list', ['limit' => 2]));
    $second = mcpResult(callMcp('announcements_list', ['limit' => 2, 'cursor' => $first['next_cursor']]));

    expect($first['has_more'])->toBeTrue()
        ->and(array_column($second['items'], 'id'))->not->toContain(...array_column($first['items'], 'id'));
});

it('المحتوى الغني بيرجع نص عادي من غير وسوم', function (): void {
    actingAsMcp('viewer');
    $announcement = draftAnnouncement(['body' => ['ar' => '<p>أهلاً</p><script>alert(1)</script>', 'en' => '<p>Hello</p><p>team</p>']]);

    $record = mcpResult(callMcp('announcements_get', ['id' => $announcement->getKey()]))['record'];

    expect($record['body']['en'])->toBe('Hello
team')
        ->and($record['body']['ar'])->not->toContain('<', 'alert');
});

// ══════════════ الكتابة: نفس قواعد اللوحة ══════════════

it('الإنشاء بيحط الكاتب والمؤسسة من الجلسة — مش من المدخلات', function (): void {
    $editor = actingAsMcp('editor');
    $other = Tenant::factory()->create();

    $result = mcpResult(callMcp('announcements_create', [
        'title' => ['ar' => 'جديد', 'en' => 'New'],
        'body' => ['ar' => 'نص', 'en' => 'Body'],
        'severity' => 'info',
        'tenant_id' => $other->getKey(),
        'author_id' => 999,
        'status' => 'published',
    ])->assertOk());

    $announcement = Announcement::query()->findOrFail($result['record']['id']);

    expect($announcement->tenant_id)->toBe(currentTenant()->getKey())
        ->and($announcement->author_id)->toBe($editor->getKey())
        ->and($announcement->status)->toBe(AnnouncementStatus::Draft);
});

it('التحقق على السيرفر: عنوان لغة الـ fallback مطلوب', function (): void {
    actingAsMcp('editor');

    callMcp('announcements_create', [
        'title' => ['ar' => 'بس عربي'],
        'body' => ['en' => 'Body'],
        'severity' => 'info',
    ])->assertHasErrors();

    expect(Announcement::query()->count())->toBe(0);
});

it('التعديل بيغيّر اللي اتبعت بس ومابيمسحش الترجمات التانية', function (): void {
    actingAsMcp('editor');
    $announcement = draftAnnouncement(['title' => ['ar' => 'قديم', 'en' => 'Old']]);

    callMcp('announcements_update', ['id' => $announcement->getKey(), 'title' => ['en' => 'New']])->assertOk();

    expect($announcement->fresh()->getTranslations('title'))->toBe(['ar' => 'قديم', 'en' => 'New']);
});

it('النشر بقواعد الـ Policy — والرفض برسالتها', function (): void {
    actingAsMcp('admin');
    $announcement = draftAnnouncement();

    callMcp('announcements_publish', ['id' => $announcement->getKey(), 'notify_users' => false])->assertOk();

    expect($announcement->fresh()->status)->toBe(AnnouncementStatus::Published);

    $again = callMcp('announcements_publish', ['id' => $announcement->getKey()])->assertHasErrors();

    expect(mcpError($again))->toBe(__('authorization.denied.announcement.not_draft'));
});

it('الإعلان المنشور مايتحذفش — نفس قاعدة السلامة في اللوحة', function (): void {
    actingAsMcp('admin');
    $announcement = Announcement::factory()->create(['status' => AnnouncementStatus::Published]);

    $response = callMcp('announcements_delete', ['id' => $announcement->getKey()])->assertHasErrors();

    expect(mcpError($response))->toBe(__('authorization.denied.announcement.published_must_archive'))
        ->and($announcement->fresh()->trashed())->toBeFalse();
});

it('المحرر مايقدرش يحذف حتى لو نادى الأداة بالاسم', function (): void {
    actingAsMcp('editor');
    $announcement = draftAnnouncement();

    callMcp('announcements_delete', ['id' => $announcement->getKey()])->assertHasErrors();

    expect($announcement->fresh()->trashed())->toBeFalse();
});

it('الحالة مابتتكتبش بالتعديل — بالنشر بس', function (): void {
    actingAsMcp('admin');
    $announcement = draftAnnouncement();

    callMcp('announcements_update', ['id' => $announcement->getKey(), 'status' => 'published', 'is_pinned' => true])->assertOk();

    expect($announcement->fresh())
        ->status->toBe(AnnouncementStatus::Draft)
        ->is_pinned->toBeFalse();
});

// ══════════════ التسجيل والحدود ══════════════

it('كل نداء بيتسجّل في سجل النشاط بمدخلات منقّاة', function (): void {
    $editor = actingAsMcp('editor');

    callMcp('announcements_create', [
        'title' => ['ar' => 'جديد', 'en' => 'New'],
        'body' => ['ar' => 'سر', 'en' => 'secret body'],
        'severity' => 'info',
    ])->assertOk();

    // أداة ظاهرة على سجل مش موجود — الرفض نفسه بيتسجّل
    callMcp('announcements_update', ['id' => 999_999, 'severity' => 'info'])->assertHasErrors();

    $logs = ActivityLog::query()->where('log_name', 'mcp')->where('description', 'like', 'announcements_%')->orderBy('id')->get();

    expect($logs)->toHaveCount(2)
        ->and($logs[0]->causer_id)->toBe($editor->getKey())
        ->and($logs[0]->event)->toBe('succeeded')
        ->and($logs[0]->properties['arguments']['body'])->toBe('[redacted]')
        ->and($logs[0]->tenantId())->toBe(currentTenant()->getKey())
        ->and($logs[1]->event)->toBe('denied');
});

it('حد معدّل منفصل للكتابة', function (): void {
    config(['ai.rate_limit.writes_per_minute' => 1]);
    actingAsMcp('editor');
    $announcement = draftAnnouncement();

    callMcp('announcements_update', ['id' => $announcement->getKey(), 'severity' => 'warning'])->assertOk();
    $blocked = callMcp('announcements_update', ['id' => $announcement->getKey(), 'severity' => 'info'])->assertHasErrors();

    expect(mcpError($blocked))->toBe(__('mcp.errors.rate_limited'));
});

// ══════════════ خريطة النظام ══════════════

it('describe_system بيعرف المستخدم والمؤسسة والشاشات اللي يقدر يفتحها بس', function (): void {
    $editor = actingAsMcp('editor', write: false);

    $result = mcpResult(callMcp('describe_system')->assertOk());
    $screens = collect($result['screens'])->keyBy('key');

    expect($result['acting_as']['id'])->toBe($editor->getKey())
        ->and($result['organization']['id'])->toBe(currentTenant()->getKey())
        ->and($result['access'])->toBe('read_only')
        ->and($screens)->toHaveKey('announcement')
        ->and($screens['announcement']['tools'])->toContain('announcements_list')
        ->and($screens['announcement']['url'])->toContain('/announcements')
        // المحرر مالوش الأدوار ولا سجل النشاط — مابيظهروش زي السايدبار
        ->and($screens)->not->toHaveKey('role')
        ->and($screens)->not->toHaveKey('activity-log');
});

it('get_screen بيرجّع الوصف من جولة الشاشة وروابط صفحات السجل', function (): void {
    actingAsMcp('admin');

    $screen = mcpResult(callMcp('get_screen', ['key' => 'announcement'])->assertOk())['screen'];

    expect($screen['description'])->toContain(__('content::content.tours.announcements.purpose.body'))
        ->and($screen['record_pages']['edit'])->toEndWith('/announcements/{id}/edit');
});

// ══════════════ مراجعة: قواعد الفورم على طريق الـ AI ══════════════

it('التعديل مايقدرش يمسح عنوان لغة الـ fallback', function (): void {
    actingAsMcp('editor');
    $announcement = draftAnnouncement(['title' => ['ar' => 'عربي', 'en' => 'English']]);
    $fallback = (string) config('app.fallback_locale');

    callMcp('announcements_update', ['id' => $announcement->getKey(), 'title' => [$fallback => '']])->assertHasErrors();

    expect($announcement->fresh()->getTranslation('title', $fallback, false))->not->toBe('');
});

it('تاريخ الانتهاء لازم بعد النشر — حتى في تعديل جزئي', function (): void {
    actingAsMcp('editor');
    $announcement = draftAnnouncement(['published_at' => now()->addDays(10), 'expires_at' => null]);

    callMcp('announcements_update', ['id' => $announcement->getKey(), 'expires_at' => now()->addDay()->toIso8601String()])
        ->assertHasErrors();

    expect($announcement->fresh()->expires_at)->toBeNull();
});

it('مفيش ترتيب بعمود nullable — ترقيم الـ cursor مابيدعمهوش', function (): void {
    actingAsMcp('viewer');

    callMcp('announcements_list', ['sort' => 'published_at'])->assertHasErrors();
    callMcp('announcements_list', ['sort' => 'created_at'])->assertOk();
});
