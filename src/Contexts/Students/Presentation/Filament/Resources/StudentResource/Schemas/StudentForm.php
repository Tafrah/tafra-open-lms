<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Schemas;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Src\Contexts\Students\Application\Actions\RegisterStudentAction;
use Src\Contexts\Students\Domain\Enums\SchoolType;
use Src\Contexts\Students\Domain\Models\Student;

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
                    // التحقق بعد توحيد الرقم (+20، أرقام عربية، مسافات) — مش على اللي اتكتب خام
                    TextInput::make('phone')
                        ->label(__('students::students.student.fields.phone'))
                        ->inputMode('tel')
                        ->required()
                        ->rule(fn (?Student $record): Closure => self::phoneRule($record, unique: true))
                        ->dehydrateStateUsing(fn (?string $state): string => RegisterStudentAction::normalizePhone((string) $state)),
                    TextInput::make('parent_phone')
                        ->label(__('students::students.student.fields.parent_phone'))
                        ->inputMode('tel')
                        ->rule(fn (): Closure => self::phoneRule(null, unique: false))
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? RegisterStudentAction::normalizePhone((string) $state) : null),
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

    private static function phoneRule(?Student $record, bool $unique): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record, $unique): void {
            if (blank($value)) {
                return;
            }

            $phone = RegisterStudentAction::normalizePhone((string) $value);

            if (preg_match('/^01[0125][0-9]{8}$/', $phone) !== 1) {
                $fail(__('students::students.student.validation.phone'));

                return;
            }

            $taken = $unique && Student::query()->withTrashed()
                ->where('phone', $phone)
                ->when($record !== null, fn ($q) => $q->whereKeyNot($record->getKey()))
                ->exists();

            if ($taken) {
                $fail(__('students::students.errors.phone_taken'));
            }
        };
    }
}
