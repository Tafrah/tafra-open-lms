<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('id')
                ->label('#')
                ->disabled(),
        ]);
    }
}
