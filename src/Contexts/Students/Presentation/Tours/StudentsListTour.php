<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Pages\ListStudents;

final class StudentsListTour extends Tour
{
    private const PREFIX = 'students::students.tours.student';

    public static function key(): string
    {
        return 'students.student';
    }

    public static function page(): string
    {
        return ListStudents::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'table', '.fi-ta-ctn'),
            self::step(self::PREFIX, 'create', '.fi-header .fi-ac'),
        ];
    }
}
