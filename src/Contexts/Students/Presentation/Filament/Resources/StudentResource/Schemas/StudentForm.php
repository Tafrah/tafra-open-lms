<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;
use Src\Contexts\Students\Application\Actions\RegisterStudentAction;
use Src\Contexts\Students\Domain\Enums\SchoolType;
use Src\Support\Application\Contracts\TenantContext;

final class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('students::students.student.sections.info'))
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('students::students.student.fields.name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label(__('students::students.student.fields.phone'))
                        ->tel()
                        ->required()
                        ->regex('/^01[0125][0-9]{8}$/')
                        ->dehydrateStateUsing(fn (?string $state): string => RegisterStudentAction::normalizePhone((string) $state))
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('tenant_id', app(TenantContext::class)->id())),
                    TextInput::make('parent_phone')
                        ->label(__('students::students.student.fields.parent_phone'))
                        ->tel()
                        ->regex('/^01[0125][0-9]{8}$/'),
                    TextInput::make('email')
                        ->label(__('students::students.student.fields.email'))
                        ->email()
                        ->maxLength(255),
                    Select::make('educational_level_id')
                        ->label(__('students::students.student.fields.level'))
                        ->relationship('level', 'name')
                        ->native(false)
                        ->preload(),
                    TextInput::make('governorate')
                        ->label(__('students::students.student.fields.governorate'))
                        ->maxLength(64),
                    Select::make('school_type')
                        ->label(__('students::students.student.fields.school_type'))
                        ->options(SchoolType::class)
                        ->native(false),
                    TextInput::make('password')
                        ->label(__('students::students.student.fields.password'))
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->required()
                        ->visibleOn('create'),
                ]),
        ]);
    }
}
