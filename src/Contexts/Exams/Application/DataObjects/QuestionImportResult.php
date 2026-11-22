<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Application\DataObjects;

/**
 * نتيجة استيراد الأسئلة: يا إما كله اتضاف، يا إما ولا حاجة + أخطاء لكل صف
 * (المدرس بيصلّح الملف ويرفعه تاني — مفيش نص ملف متضاف).
 */
final readonly class QuestionImportResult
{
    /** @param array<int, list<string>> $errors رقم الصف في الملف ← الأخطاء */
    public function __construct(
        public int $imported,
        public array $errors,
    ) {}

    public function failed(): bool
    {
        return $this->errors !== [];
    }

    /** سطر لكل صف — للإشعار */
    public function errorLines(int $limit = 15): string
    {
        $lines = [];

        foreach (array_slice($this->errors, 0, $limit, true) as $row => $messages) {
            $lines[] = __('exams::exams.import.row_error', ['row' => $row, 'errors' => implode(' · ', $messages)]);
        }

        if (count($this->errors) > $limit) {
            $lines[] = __('exams::exams.import.more_errors', ['count' => count($this->errors) - $limit]);
        }

        return implode("\n", $lines);
    }
}
