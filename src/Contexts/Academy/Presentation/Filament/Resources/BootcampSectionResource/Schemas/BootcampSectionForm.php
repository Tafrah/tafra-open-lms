<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;

final class BootcampSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('academy::academy.section.fields.name'))
                        ->required()
                        ->maxLength(255),
                    Select::make('publish_status')
                        ->label(__('academy::academy.section.fields.publish_status'))
                        ->options(PublishStatus::class)
                        ->native(false)
                        ->required(),
                    Toggle::make('is_free_preview')
                        ->label(__('academy::academy.section.fields.is_free_preview')),
                ]),
        ]);
    }
}
