<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Contexts\Academy\Domain\Enums\LessonType;
use Src\Contexts\Academy\Domain\Enums\SectionItemType;
use Src\Contexts\Academy\Domain\Models\BootcampSection;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Academy\Domain\Models\OnlineLesson;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource\Pages\EditBootcampSection;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource\RelationManagers\ItemsRelationManager;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource\Pages\EditOnlineLesson;
use Src\Support\Application\Contracts\DiskResolver;

beforeEach(fn () => inTenant());

it('المدرس بيحدد مواعيد ظهور العنصر وبيشوفه في اللوحة دايمًا حتى لو مخفي', function (): void {
    actingAsRole('admin');
    $section = BootcampSection::factory()->create();
    $lesson = OnlineLesson::factory()->create();
    $from = now()->addWeek()->startOfMinute();

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $section, 'pageClass' => EditBootcampSection::class])
        ->callTableAction('create', data: [
            'type' => SectionItemType::Lesson->value,
            'online_lesson_id' => $lesson->getKey(),
            'publish_status' => 'published',
            'visible_from' => $from->format('Y-m-d H:i'),
        ])
        ->assertHasNoTableActionErrors();

    $item = BootcampSectionItem::query()->where('bootcamp_section_id', $section->getKey())->firstOrFail();
    expect($item->visible_from?->equalTo($from))->toBeTrue()
        ->and($item->visible_until)->toBeNull()
        ->and($item->isVisibleNow())->toBeFalse();

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $section, 'pageClass' => EditBootcampSection::class])
        ->loadTable()
        ->assertCanSeeTableRecords([$item]);
});

it('«يظهر لحد» لازم يكون بعد «يظهر من»', function (): void {
    actingAsRole('admin');
    $section = BootcampSection::factory()->create();

    Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $section, 'pageClass' => EditBootcampSection::class])
        ->callTableAction('create', data: [
            'type' => SectionItemType::Lesson->value,
            'online_lesson_id' => OnlineLesson::factory()->create()->getKey(),
            'publish_status' => 'published',
            'visible_from' => now()->addDays(2)->format('Y-m-d H:i'),
            'visible_until' => now()->addDay()->format('Y-m-d H:i'),
        ])
        ->assertHasTableActionErrors(['visible_until']);
});

it('درس الـ PDF: «مسموح بالتنزيل» مقفول افتراضيًا وبيتحفظ لما يتفتح', function (): void {
    actingAsRole('admin');
    Storage::fake(app(DiskResolver::class)->for('documents'));
    $lesson = OnlineLesson::factory()->create(['type' => LessonType::Pdf, 'external_url' => null]);
    $lesson->addMedia(UploadedFile::fake()->createWithContent('n.pdf', "%PDF-1.4\n%%EOF"))
        ->toMediaCollection('documents', app(DiskResolver::class)->for('documents'));

    expect($lesson->fresh()->allow_download)->toBeFalse();

    Livewire::test(EditOnlineLesson::class, ['record' => $lesson->getKey()])
        ->assertFormFieldVisible('allow_download')
        ->fillForm(['allow_download' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($lesson->fresh()->allow_download)->toBeTrue();
});
