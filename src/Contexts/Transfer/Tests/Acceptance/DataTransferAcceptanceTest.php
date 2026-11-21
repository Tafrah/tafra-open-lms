<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Contexts\Transfer\Application\Actions\ExportAcademyAction;
use Src\Contexts\Transfer\Presentation\Filament\Pages\DataTransfer;

it('المدير بيفتح صفحة نقل البيانات والمساعد لأ', function (): void {
    actingAsRole('admin');
    $this->get(DataTransfer::getUrl())->assertOk();

    actingAsRole('assistant');
    $this->get(DataTransfer::getUrl())->assertForbidden();
});

it('التصدير من اللوحة بينزّل ملف zip', function (): void {
    actingAsRole('admin');
    Bootcamp::factory()->create();

    Livewire::test(DataTransfer::class)
        ->callAction('export')
        ->assertFileDownloaded();
});

it('الاستيراد من اللوحة: تجربة بتقرير، وبعدين تأكيد', function (): void {
    Storage::fake('local');
    inTenant();
    Bootcamp::factory()->create(['slug' => 'from-file']);
    $zip = storage_path('app/testing/panel-import.zip');
    app(ExportAcademyAction::class)->handle($zip);

    $target = Tenant::factory()->create();
    actingAsRole('admin', $target);

    $page = Livewire::test(DataTransfer::class)
        ->callAction('import', data: [
            'file' => UploadedFile::fake()->createWithContent('export.zip', (string) file_get_contents($zip)),
        ])
        ->assertHasNoActionErrors();

    expect($page->get('report')['dry_run'])->toBeTrue()
        ->and(Bootcamp::query()->count())->toBe(0);

    $page->callAction('confirm');

    expect(Bootcamp::query()->where('slug', 'from-file')->exists())->toBeTrue()
        ->and($page->get('pendingPath'))->toBeNull();
});

it('المتصفح مايقدرش يغيّر مسار الملف (حماية من الإشارة لملفات السيرفر)', function (): void {
    actingAsRole('admin');

    expect(fn () => Livewire::test(DataTransfer::class)->set('pendingPath', 'transfer-uploads/../../.env'))
        ->toThrow(Exception::class);
});
