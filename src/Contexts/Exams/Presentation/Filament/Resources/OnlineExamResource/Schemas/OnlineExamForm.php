<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Src\Contexts\Exams\Domain\Models\OnlineExam;

final class OnlineExamForm
{
    private const NEVER = 'never';

    private const AFTER_FINISH = 'after_finish';

    private const AFTER_DATE = 'after_date';

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
                    // ٣ حالات فوق عمودين: show_correct_answers_after_finish (عمود طفرة) + show_answers_at
                    Select::make('show_answers_mode')
                        ->label(__('exams::exams.exam.fields.show_answers'))
                        ->options(self::answerModes())
                        ->default(self::AFTER_FINISH)
                        ->native(false)
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Select $component, ?OnlineExam $record): void {
                            if ($record !== null) {
                                $component->state(self::modeOf($record));
                            }
                        })
                        ->afterStateUpdated(fn (?string $state, Set $set) => $set('show_correct_answers_after_finish', $state !== self::NEVER))
                        ->required(),
                    Hidden::make('show_correct_answers_after_finish')
                        ->default(true),
                    DateTimePicker::make('show_answers_at')
                        ->label(__('exams::exams.exam.fields.show_answers_at'))
                        ->helperText(__('exams::exams.exam.help.show_answers_at'))
                        ->seconds(false)
                        ->visible(fn (Get $get): bool => $get('show_answers_mode') === self::AFTER_DATE)
                        ->required(fn (Get $get): bool => $get('show_answers_mode') === self::AFTER_DATE)
                        ->dehydratedWhenHidden()
                        ->dehydrateStateUsing(fn (mixed $state, Get $get): mixed => $get('show_answers_mode') === self::AFTER_DATE ? $state : null),
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

    /** @return array<string, string> */
    private static function answerModes(): array
    {
        return [
            self::NEVER => __('exams::exams.exam.show_answers.never'),
            self::AFTER_FINISH => __('exams::exams.exam.show_answers.after_finish'),
            self::AFTER_DATE => __('exams::exams.exam.show_answers.after_date'),
        ];
    }

    private static function modeOf(OnlineExam $exam): string
    {
        return match (true) {
            ! $exam->show_correct_answers_after_finish => self::NEVER,
            $exam->show_answers_at !== null => self::AFTER_DATE,
            default => self::AFTER_FINISH,
        };
    }
}
