<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Academy\Domain\Models\OnlineLesson;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource\Pages\ListOnlineLessons;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

it('المدير بيفتح شاشة OnlineLesson ويشوف السجلات', function (): void {
    actingAsRole('admin');
    $record = OnlineLesson::factory()->create();

    Livewire::test(ListOnlineLessons::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$record]);
});

it('المشاهد مايشوفش زراير التعديل في OnlineLesson', function (): void {
    actingAsRole('viewer');

    $this->get(OnlineLessonResource::getUrl('index'))->assertOk();
});

it('شاشة OnlineLesson مابتعرضش سجلات مؤسسة تانية', function (): void {
    actingAsRole('admin');
    $mine = OnlineLesson::factory()->create();

    $other = Tenant::factory()->create();
    $foreign = app(TenantContext::class)->forTenant($other->getKey(), fn () => OnlineLesson::factory()->create());

    Livewire::test(ListOnlineLessons::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$foreign]);
});
