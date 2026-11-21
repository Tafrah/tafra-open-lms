<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\EducationalLevelResource\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;
use Src\Support\Application\Contracts\TenantContext;

final class EducationalLevelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('academy::academy.level.fields.name'))
                ->required()
                ->maxLength(120),
            TextInput::make('code')
                ->label(__('academy::academy.level.fields.code'))
                ->helperText(__('academy::academy.level.help.code'))
                ->required()
                ->maxLength(32)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('tenant_id', app(TenantContext::class)->id())),
            ColorPicker::make('color')
                ->label(__('academy::academy.level.fields.color')),
            TextInput::make('order')
                ->label(__('academy::academy.level.fields.order'))
                ->numeric()
                ->minValue(0)
                ->default(0),
        ]);
    }
}
