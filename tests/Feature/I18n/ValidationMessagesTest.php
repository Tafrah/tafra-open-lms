<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource\Pages\CreateTenant;

/*
|--------------------------------------------------------------------------
| رسائل التحقق بلغة الواجهة (docs/21 بند ٣٠)
|--------------------------------------------------------------------------
| رحلة إنشاء مؤسسة عرضت «The الاسم field is required.» في الواجهة العربية:
| مفيش `lang/ar/validation.php`، فـ Laravel رجع لرسايله الإنجليزي بصمت.
*/

it('ملفات Laravel الأساسية مترجمة للعربي', function (string $file): void {
    expect(lang_path("ar/{$file}.php"))->toBeFile();
})->with(['validation', 'auth', 'passwords', 'pagination']);

it('رسالة الحقل الإلزامي في فورم Filament بتظهر بالعربي', function (): void {
    actingAsCentral('super_admin');
    app()->setLocale('ar');

    $component = Livewire::test(CreateTenant::class)
        ->fillForm(['slug' => 'no-name'])
        ->call('create')
        ->assertHasFormErrors(['name.'.config('app.fallback_locale') => 'required']);

    $messages = collect($component->errors()->all())->implode(' ');

    expect($messages)->not->toContain('field is required')
        ->and($messages)->toContain('مطلوب');
});
