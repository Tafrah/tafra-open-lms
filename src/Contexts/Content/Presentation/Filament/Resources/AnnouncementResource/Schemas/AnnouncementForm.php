<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Src\Contexts\Content\Domain\Enums\AnnouncementSeverity;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Support\Application\Contracts\DiskResolver;

final class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('content::content.announcement.sections.content'))
                ->columnSpanFull()
                ->schema([
                    Tabs::make('translations')
                        ->columnSpanFull()
                        ->tabs(collect((array) config('app.supported_locales'))
                            ->map(fn (string $locale): Tabs\Tab => Tabs\Tab::make($locale)
                                ->label(__("common.locales.{$locale}"))
                                ->schema([
                                    TextInput::make("title.{$locale}")
                                        ->label(__('content::content.announcement.fields.title'))
                                        ->required($locale === config('app.fallback_locale'))
                                        ->maxLength((int) config('content.title_max_length', 180)),

                                    RichEditor::make("body.{$locale}")
                                        ->label(__('content::content.announcement.fields.body'))
                                        ->required($locale === config('app.fallback_locale'))
                                        // المحتوى بيتنقّى عند الحفظ في الموديل، وعند العرض في الـ Blade
                                        ->helperText(__('content::content.announcement.help.body'))
                                        ->columnSpanFull(),
                                ]))
                            ->all()),
                ]),

            Section::make(__('content::content.announcement.sections.settings'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('severity')
                        ->label(__('content::content.announcement.fields.severity'))
                        ->options(AnnouncementSeverity::class)
                        ->default(AnnouncementSeverity::Info)
                        ->native(false)
                        ->required(),

                    Toggle::make('is_pinned')
                        ->label(__('content::content.announcement.fields.pinned'))
                        // ⚠️ الإخفاء لوحده مش أمان — لازم منع حفظ فعلي كمان
                        ->visible(fn (?Announcement $record): bool => $record !== null
                            && (auth()->user()?->can('pin', $record) ?? false))
                        ->saved(fn (?Announcement $record): bool => $record !== null
                            && (auth()->user()?->can('pin', $record) ?? false)),

                    DateTimePicker::make('published_at')
                        ->label(__('content::content.announcement.fields.published_at'))
                        ->seconds(false),

                    DateTimePicker::make('expires_at')
                        ->label(__('content::content.announcement.fields.expires_at'))
                        ->seconds(false)
                        ->after('published_at'),

                    SpatieMediaLibraryFileUpload::make('attachments')
                        ->label(__('content::content.announcement.fields.attachments'))
                        ->collection('attachments')
                        ->disk(fn (): string => app(DiskResolver::class)->for('attachments'))
                        ->multiple()
                        ->reorderable()
                        ->downloadable()
                        ->openable()
                        ->maxSize(10240)
                        // قائمة MIME بيضاء — ومفيش SVG (docs/20 بند ٧)
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
