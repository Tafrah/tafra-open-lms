<?php

declare(strict_types=1);

namespace Src\Contexts\Students;

use Illuminate\Support\Facades\Event;
use Src\Contexts\Billing\Domain\Events\InvoiceReviewed;
use Src\Contexts\Exams\Domain\Events\AttemptGraded;
use Src\Contexts\Students\Infrastructure\Listeners\NotifyStudentOfGradedAttempt;
use Src\Contexts\Students\Infrastructure\Listeners\NotifyStudentOfInvoiceReview;
use Src\Support\Infrastructure\Providers\ContextServiceProvider;

final class StudentsServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'students';
    }

    protected function path(): string
    {
        return __DIR__;
    }

    /** @return list<string> */
    protected function panelIds(): array
    {
        return ['admin'];
    }

    protected function bootContext(): void
    {
        // إشعارات الطالب على الموقع — السياقات بتتكلم بالأحداث (docs/01)
        Event::listen(InvoiceReviewed::class, NotifyStudentOfInvoiceReview::class);
        Event::listen(AttemptGraded::class, NotifyStudentOfGradedAttempt::class);
    }
}
