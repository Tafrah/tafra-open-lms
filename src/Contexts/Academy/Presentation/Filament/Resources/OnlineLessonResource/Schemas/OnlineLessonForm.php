<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Src\Contexts\Academy\Domain\Enums\LessonType;
use Src\Contexts\Academy\Domain\Models\OnlineLesson;
use Src\Support\Application\Contracts\DiskResolver;

final class OnlineLessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('academy::academy.lesson.sections.content'))
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('academy::academy.lesson.fields.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Select::make('type')
                        ->label(__('academy::academy.lesson.fields.type'))
                        ->options(LessonType::class)
                        ->default(LessonType::YoutubeVideo)
                        ->native(false)
                        ->live()
                        ->required(),
                    TextInput::make('duration_in_seconds')
                        ->label(__('academy::academy.lesson.fields.duration_minutes'))
                        ->numeric()
                        ->minValue(0)
                        ->formatStateUsing(fn (?int $state): ?int => $state !== null ? intdiv($state, 60) : null)
                        ->dehydrateStateUsing(fn (mixed $state): int => (int) $state * 60),
                    TextInput::make('external_url')
                        ->label(__('academy::academy.lesson.fields.youtube_url'))
                        ->helperText(__('academy::academy.lesson.help.youtube_url'))
                        ->url()
                        ->maxLength(2048)
                        ->required(fn (Get $get): bool => self::type($get) === LessonType::YoutubeVideo)
                        ->visible(fn (Get $get): bool => self::type($get) === LessonType::YoutubeVideo)
                        ->rule(fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                            if (filled($value) && (new OnlineLesson(['external_url' => $value]))->youtubeId() === null) {
                                $fail(__('academy::academy.lesson.validation.youtube'));
                            }
                        })
                        ->columnSpanFull(),
                    SpatieMediaLibraryFileUpload::make('documents')
                        ->label(__('academy::academy.lesson.fields.pdf'))
                        ->collection('documents')
                        ->disk(fn (): string => app(DiskResolver::class)->for('documents'))
                        ->visibility('private')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(51200)
                        ->required(fn (Get $get): bool => self::type($get) === LessonType::Pdf)
                        ->visible(fn (Get $get): bool => self::type($get) === LessonType::Pdf)
                        ->columnSpanFull(),
                    Toggle::make('allow_download')
                        ->label(__('academy::academy.lesson.fields.allow_download'))
                        ->helperText(__('academy::academy.lesson.help.allow_download'))
                        ->default(false)
                        ->visible(fn (Get $get): bool => self::type($get) === LessonType::Pdf)
                        ->columnSpanFull(),
                    RichEditor::make('text')
                        ->label(__('academy::academy.lesson.fields.text'))
                        ->required(fn (Get $get): bool => self::type($get) === LessonType::Text)
                        ->visible(fn (Get $get): bool => self::type($get) === LessonType::Text)
                        ->columnSpanFull(),
                    Textarea::make('description')
                        ->label(__('academy::academy.lesson.fields.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * الحقول المخفية ماتتبعتش — فلما المدرس يغيّر نوع الدرس، بقايا النوع القديم
     * بتفضل في القاعدة. هنا بنفضّيها عشان الطالب مايشوفش لينك يوتيوب قديم في درس نصي.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function clearOtherTypes(array $data): array
    {
        $type = $data['type'] instanceof LessonType ? $data['type'] : LessonType::tryFrom((string) ($data['type'] ?? ''));

        if ($type !== LessonType::YoutubeVideo) {
            $data['external_url'] = null;
        }

        if ($type !== LessonType::Text) {
            $data['text'] = null;
        }

        if ($type !== LessonType::Pdf) {
            $data['allow_download'] = false;
        }

        return $data;
    }

    /** ملف الـ PDF بيتشال لو الدرس بقى نوع تاني. */
    public static function clearDocumentsUnlessPdf(OnlineLesson $lesson): void
    {
        if ($lesson->type !== LessonType::Pdf) {
            $lesson->clearMediaCollection('documents');
        }
    }

    private static function type(Get $get): ?LessonType
    {
        $type = $get('type');

        return $type instanceof LessonType ? $type : LessonType::tryFrom((string) $type);
    }
}
