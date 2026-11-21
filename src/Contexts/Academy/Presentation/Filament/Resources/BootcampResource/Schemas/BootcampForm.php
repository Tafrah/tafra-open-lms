<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Src\Support\Application\Contracts\DiskResolver;
use Src\Support\Application\Contracts\TenantContext;

final class BootcampForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('bootcamp')
                ->columnSpanFull()
                ->persistTabInQueryString()
                ->tabs([
                    Tab::make(__('academy::academy.bootcamp.sections.basic'))
                        ->icon('heroicon-o-information-circle')
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label(__('academy::academy.bootcamp.fields.name'))
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation): void {
                                    if ($operation === 'create' && blank($get('slug'))) {
                                        $set('slug', self::slug((string) $state));
                                    }
                                }),
                            TextInput::make('slug')
                                ->label(__('academy::academy.bootcamp.fields.slug'))
                                ->helperText(__('academy::academy.bootcamp.help.slug'))
                                ->required()
                                ->maxLength(255)
                                ->alphaDash()
                                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('tenant_id', app(TenantContext::class)->id())),
                            Select::make('educational_level_id')
                                ->label(__('academy::academy.bootcamp.fields.level'))
                                ->relationship('level', 'name')
                                ->native(false)
                                ->preload(),
                            TextInput::make('promotional_video_url')
                                ->label(__('academy::academy.bootcamp.fields.promo_video'))
                                ->url()
                                ->maxLength(2048),
                            Textarea::make('short_description')
                                ->label(__('academy::academy.bootcamp.fields.short_description'))
                                ->maxLength(500)
                                ->rows(2)
                                ->columnSpanFull(),
                            RichEditor::make('description')
                                ->label(__('academy::academy.bootcamp.fields.description'))
                                ->columnSpanFull(),
                            SpatieMediaLibraryFileUpload::make('thumbnail')
                                ->label(__('academy::academy.bootcamp.fields.thumbnail'))
                                ->helperText(__('academy::academy.bootcamp.help.thumbnail'))
                                ->collection('thumbnail')
                                ->disk(fn (): string => app(DiskResolver::class)->for('thumbnail'))
                                ->image()
                                ->imageEditor()
                                ->imageAspectRatio('16:9')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(4096)
                                ->columnSpanFull(),
                        ]),
                    Tab::make(__('academy::academy.bootcamp.sections.pricing'))
                        ->icon('heroicon-o-banknotes')
                        ->columns(2)
                        ->schema([
                            TextInput::make('price')
                                ->label(__('academy::academy.bootcamp.fields.price'))
                                ->helperText(__('academy::academy.bootcamp.help.price'))
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->suffix(__('academy::academy.currency'))
                                ->required(),
                            TextInput::make('discount_price')
                                ->label(__('academy::academy.bootcamp.fields.discount_price'))
                                ->numeric()
                                ->minValue(0)
                                ->lt('price')
                                ->suffix(__('academy::academy.currency')),
                            DateTimePicker::make('discount_start_date')
                                ->label(__('academy::academy.bootcamp.fields.discount_start_date'))
                                ->seconds(false),
                            DateTimePicker::make('discount_end_date')
                                ->label(__('academy::academy.bootcamp.fields.discount_end_date'))
                                ->seconds(false)
                                ->after('discount_start_date'),
                            TextInput::make('can_watch_in_days')
                                ->label(__('academy::academy.bootcamp.fields.can_watch_in_days'))
                                ->helperText(__('academy::academy.bootcamp.help.can_watch_in_days'))
                                ->numeric()
                                ->minValue(1)
                                ->suffix(__('academy::academy.days')),
                        ]),
                    Tab::make(__('academy::academy.bootcamp.sections.display'))
                        ->icon('heroicon-o-eye')
                        ->columns(2)
                        ->schema([
                            Toggle::make('is_featured')
                                ->label(__('academy::academy.bootcamp.fields.is_featured'))
                                ->helperText(__('academy::academy.bootcamp.help.is_featured')),
                            TextInput::make('featured_order')
                                ->label(__('academy::academy.bootcamp.fields.featured_order'))
                                ->numeric()
                                ->minValue(0),
                        ]),
                ]),
        ]);
    }

    private static function slug(string $name): string
    {
        $slug = Str::slug($name);

        // الأسماء العربية بتطلع فاضية من Str::slug — نخلّي الرابط قصير ومميّز
        return $slug !== '' ? $slug : 'course-'.Str::lower(Str::random(6));
    }
}
