<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\DataObjects;

/**
 * نتيجة التفعيل/الإلغاء الجماعي بأرقام الموبايل — كل رقم في خانة واحدة بس.
 */
final readonly class BulkEnrollmentResult
{
    /**
     * @param list<string> $done اتفعّل (أو اتلغى في الإلغاء)
     * @param list<string> $skipped مشترك أصلًا (التفعيل) / مش مشترك أصلًا (الإلغاء)
     * @param list<string> $notFound مفيش طالب بالرقم ده
     * @param list<string> $insufficientBalance رصيده مش كفاية (التفعيل بالخصم من المحفظة)
     * @param array<string, string> $failed رقم ← سبب الرفض
     */
    public function __construct(
        public array $done = [],
        public array $skipped = [],
        public array $notFound = [],
        public array $insufficientBalance = [],
        public array $failed = [],
    ) {}

    public function total(): int
    {
        return count($this->done) + count($this->skipped) + count($this->notFound)
            + count($this->insufficientBalance) + count($this->failed);
    }
}
