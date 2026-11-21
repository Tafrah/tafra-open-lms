<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource\Pages\ListAnnouncements;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource\Pages\ListUsers;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

/*
|--------------------------------------------------------------------------
| الأداء — أي صفحة جدول فوق ١٠ استعلامات = باگ (docs/08 بند ٩)
|--------------------------------------------------------------------------
*/

it('صفحة قائمة المستخدمين لا تتجاوز ١٠ استعلامات', function (): void {
    actingAsRole('admin');
    User::factory()->count(40)->create()->each(
        fn (User $user) => $user->tenants()->attach(currentTenant()->getKey(), ['joined_at' => now()]),
    );

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::test(ListUsers::class)->assertOk();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($count)->toBeLessThanOrEqual(10, "عدد الاستعلامات: {$count}");
});

it('صفحة قائمة الإعلانات لا تتجاوز ١٠ استعلامات', function (): void {
    actingAsRole('admin');
    Announcement::factory()->count(60)->create();

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::test(ListAnnouncements::class)->assertOk();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($count)->toBeLessThanOrEqual(10, "عدد الاستعلامات: {$count}");
});

it('كل عمود قابل للبحث أو الترتيب عليه فهرس', function (): void {
    // الأعمدة اللي بنبحث/نرتّب بيها في الجداول الرئيسية
    $expected = [
        'users' => ['status', 'is_active', 'last_login_at', 'created_at', 'code', 'email'],
        'announcements' => ['tenant_id', 'status', 'published_at', 'is_pinned', 'created_at'],
        'tenants' => ['slug', 'is_active', 'created_at'],
    ];

    $missing = [];

    // جداول المؤسسات في وضع multi في قاعدة المؤسسة — والمركزية في المركزية
    $tenantTables = ['announcements'];

    foreach ($expected as $table => $columns) {
        $indexes = in_array($table, $tenantTables, true)
            ? app(TenantContext::class)->forTenant(currentTenant()->getKey(), static fn (): array => Schema::getIndexes($table))
            : Schema::connection(TenancyMode::centralConnection())->getIndexes($table);

        $indexed = collect($indexes)
            ->flatMap(fn (array $index): array => $index['columns'])
            ->unique()
            ->all();

        foreach ($columns as $column) {
            if (! in_array($column, $indexed, true)) {
                $missing[] = "{$table}.{$column}";
            }
        }
    }

    expect($missing)->toBeEmpty('أعمدة بحث/ترتيب من غير فهرس: '.implode(', ', $missing));
});

it('بادچات السايدبار مكاشة ولا تضرب قاعدة البيانات مرتين', function (): void {
    actingAsRole('admin');

    UserResource::getNavigationBadge();

    DB::flushQueryLog();
    DB::enableQueryLog();

    UserResource::getNavigationBadge();
    UserResource::getNavigationBadge();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($count)->toBe(0, "البادچ عمل {$count} استعلام رغم الكاش");
});
