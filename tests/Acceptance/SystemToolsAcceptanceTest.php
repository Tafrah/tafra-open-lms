<?php

declare(strict_types=1);

use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Contexts\Audit\Presentation\Filament\Central\Pages\Backups;
use Src\Contexts\Audit\Presentation\Filament\Central\Pages\LogViewer;
use Src\Contexts\Audit\Presentation\Filament\Central\Pages\SystemHealth;

/*
|--------------------------------------------------------------------------
| معايير قبول أدوات النظام في /central (docs/11 · docs/24 بند ٧)
|--------------------------------------------------------------------------
| صحة النظام، النسخ الاحتياطي، وعارض السجلات — لفريق التشغيل بس.
*/

dataset('system_tools', [
    'health' => [SystemHealth::class],
    'backups' => [Backups::class],
    'logs' => [LogViewer::class],
]);

it('المدير العام بيفتح كل أداة من /central', function (string $page): void {
    actingAsCentral('super_admin');

    $this->get($page::getUrl(panel: 'central'))->assertOk();
})->with('system_tools');

it('الدعم بيشوف صحة النظام بس — النسخ والسجلات 403', function (): void {
    actingAsCentral('support');

    $this->get(SystemHealth::getUrl(panel: 'central'))->assertOk();
    $this->get(Backups::getUrl(panel: 'central'))->assertForbidden();
    $this->get(LogViewer::getUrl(panel: 'central'))->assertForbidden();
});

it('أدوات النظام مش موجودة في لوحة المؤسسة أصلاً', function (string $slug): void {
    actingAsRole('admin');

    $this->get('/admin/t/'.currentTenant()->slug.'/'.$slug)->assertNotFound();
})->with(['system-health', 'backups', 'log-viewer']);

it('عارض السجلات بيخفي الأسرار قبل العرض', function (): void {
    actingAsCentral('super_admin');

    $file = 'fc-acceptance-'.uniqid().'.log';
    File::put(storage_path('logs/'.$file), 'login password=hunter2-secret {"token":"tok-secret-123"}');

    try {
        Livewire::test(LogViewer::class)
            ->set('file', $file)
            ->assertDontSee('hunter2-secret')
            ->assertDontSee('tok-secret-123')
            ->assertSee('[محذوف]');
    } finally {
        File::delete(storage_path('logs/'.$file));
    }
});

it('النسخ بتتعرض من قرص الوجهة وتشغيلها بيروح الطابور', function (): void {
    actingAsCentral('super_admin');

    $disk = (string) collect((array) config('backup.backup.destination.disks'))->first();
    Storage::fake($disk);
    Storage::disk($disk)->put(config('backup.backup.name').'/2026-09-13-backup.zip', 'zip');

    Queue::fake();

    Livewire::test(Backups::class)
        ->assertSee('2026-09-13-backup.zip')
        ->callAction('run');

    Queue::assertPushed(CallQueuedClosure::class);
});

it('صحة النظام بتفتح من غير نتايج سابقة', function (): void {
    actingAsCentral('super_admin');

    Livewire::test(SystemHealth::class)->assertOk();
});
