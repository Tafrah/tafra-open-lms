<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class OnlineExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('exams::exams.exam.sections.basic'))
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('exams::exams.exam.fields.name'))
                        ->required()
                        ->maxLength(255),
                    Select::make('educational_level_id')
                        ->label(__('exams::exams.exam.fields.level'))
                        ->relationship('level', 'name')
                        ->native(false)
                        ->preload(),
                    Textarea::make('description')
                        ->label(__('exams::exams.exam.fields.description'))
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
            Section::make(__('exams::exams.exam.sections.rules'))
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextInput::make('duration_in_seconds')
                        ->label(__('exams::exams.exam.fields.duration_minutes'))
                        ->helperText(__('exams::exams.exam.help.duration'))
                        ->numeric()
                        ->minValue(1)
                        ->formatStateUsing(fn (?int $state): ?int => $state !== null ? intdiv($state, 60) : null)
                        ->dehydrateStateUsing(fn (mixed $state): ?int => filled($state) ? (int) $state * 60 : null),
                    TextInput::make('allowed_chances')
                        ->label(__('exams::exams.exam.fields.allowed_chances'))
                        ->helperText(__('exams::exams.exam.help.chances'))
                        ->numeric()
                        ->minValue(1),
                    TextInput::make('min_percentage')
                        ->label(__('exams::exams.exam.fields.min_percentage'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(50)
                        ->suffix('%')
                        ->required(),
                    DateTimePicker::make('start_at')
                        ->label(__('exams::exams.exam.fields.start_at'))
                        ->seconds(false),
                    DateTimePicker::make('end_at')
                        ->label(__('exams::exams.exam.fields.end_at'))
                        ->seconds(false)
                        ->after('start_at'),
                ]),
            Section::make(__('exams::exams.exam.sections.display'))
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Toggle::make('show_correct_answers_after_finish')
                        ->label(__('exams::exams.exam.fields.show_correct_answers'))
                        ->default(true),
                    Toggle::make('show_explanation')
                        ->label(__('exams::exams.exam.fields.show_explanation'))
                        ->default(true),
                    Toggle::make('random_questions')
                        ->label(__('exams::exams.exam.fields.random_questions')),
                    Toggle::make('random_choices')
                        ->label(__('exams::exams.exam.fields.random_choices')),
                ]),
        ]);
    }
}
