<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\Pages\ListOnlinePassedExams;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\Pages\ViewOnlinePassedExam;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

it('المدير بيفتح شاشة OnlinePassedExam ويشوف السجلات', function (): void {
    actingAsRole('admin');
    $record = OnlinePassedExam::factory()->create(['finished' => true]);

    Livewire::test(ListOnlinePassedExams::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$record]);
});

it('المشاهد مايشوفش زراير التعديل في OnlinePassedExam', function (): void {
    actingAsRole('viewer');

    $this->get(OnlinePassedExamResource::getUrl('index'))->assertOk();
});

it('شاشة OnlinePassedExam مابتعرضش سجلات مؤسسة تانية', function (): void {
    actingAsRole('admin');
    $mine = OnlinePassedExam::factory()->create(['finished' => true]);

    $other = Tenant::factory()->create();
    $foreign = app(TenantContext::class)->forTenant($other->getKey(), fn () => OnlinePassedExam::factory()->create(['finished' => true]));

    Livewire::test(ListOnlinePassedExams::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('صفحة عرض OnlinePassedExam بتفتح', function (): void {
    actingAsRole('admin');
    $record = OnlinePassedExam::factory()->create(['finished' => true]);

    Livewire::test(ViewOnlinePassedExam::class, ['record' => $record->getKey()])->assertOk();
});
