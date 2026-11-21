<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\Pages\ListOnlineExams;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

it('المدير بيفتح شاشة OnlineExam ويشوف السجلات', function (): void {
    actingAsRole('admin');
    $record = OnlineExam::factory()->create();

    Livewire::test(ListOnlineExams::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$record]);
});

it('المشاهد مايشوفش زراير التعديل في OnlineExam', function (): void {
    actingAsRole('viewer');

    $this->get(OnlineExamResource::getUrl('index'))->assertOk();
});

it('شاشة OnlineExam مابتعرضش سجلات مؤسسة تانية', function (): void {
    actingAsRole('admin');
    $mine = OnlineExam::factory()->create();

    $other = Tenant::factory()->create();
    $foreign = app(TenantContext::class)->forTenant($other->getKey(), fn () => OnlineExam::factory()->create());

    Livewire::test(ListOnlineExams::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$foreign]);
});
