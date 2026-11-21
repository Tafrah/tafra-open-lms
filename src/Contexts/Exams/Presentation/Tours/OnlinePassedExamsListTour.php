<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\Pages\ListOnlinePassedExams;

final class OnlinePassedExamsListTour extends Tour
{
    private const PREFIX = 'exams::exams.tours.attempt';

    public static function key(): string
    {
        return 'exams.attempt';
    }

    public static function page(): string
    {
        return ListOnlinePassedExams::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'table', '.fi-ta-ctn'),
        ];
    }
}
