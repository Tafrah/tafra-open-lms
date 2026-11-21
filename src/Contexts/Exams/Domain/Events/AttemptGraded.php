<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Domain\Events;

/** كل المقالي في المحاولة اتصحح — النتيجة النهائية جاهزة للطالب. */
final readonly class AttemptGraded
{
    public function __construct(public int $attemptId) {}
}
