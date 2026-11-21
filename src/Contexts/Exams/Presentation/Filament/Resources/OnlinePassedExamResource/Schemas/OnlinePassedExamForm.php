<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class OnlinePassedExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('student_total_mark')
                ->label(__('exams::exams.attempt.fields.score'))
                ->disabled(),
        ]);
    }
}
