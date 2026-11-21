<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\Pages\ListOnlineExams;

final class OnlineExamsListTour extends Tour
{
    private const PREFIX = 'exams::exams.tours.exam';

    public static function key(): string
    {
        return 'exams.exam';
    }

    public static function page(): string
    {
        return ListOnlineExams::class;
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
