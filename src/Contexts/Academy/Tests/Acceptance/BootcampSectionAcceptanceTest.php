<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Academy\Domain\Models\BootcampSection;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource\Pages\EditBootcampSection;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource\Pages\ListBootcampSections;

it('المدير بيفتح محتوى الجزء', function (): void {
    actingAsRole('admin');
    $record = BootcampSection::factory()->create();

    Livewire::test(EditBootcampSection::class, ['record' => $record->getKey()])->assertOk();
});

it('شاشة الأجزاء مابتعرضش أجزاء مؤسسة تانية', function (): void {
    actingAsRole('admin');
    $mine = BootcampSection::factory()->create();

    Livewire::test(ListBootcampSections::class)->loadTable()->assertCanSeeTableRecords([$mine]);
});
