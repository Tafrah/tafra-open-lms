<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Application\Services;

use League\Csv\Reader;
use League\Csv\Writer;

/**
 * ملف أسئلة الاختيار من متعدد (CSV — أو Excel متحفوظ CSV).
 * الأعمدة: question, choice_1..choice_N, correct (رقم الاختيار الصح), mark, explanation.
 *
 * Excel العربي على ويندوز بيحفظ «CSV (Comma delimited)» بترميز Windows-1256
 * وأحيانًا بفاصلة منقوطة — فبنكتشف الاتنين بدل ما نطلب من المدرس يعرف الفرق.
 */
final readonly class QuestionCsv
{
    /** @return list<string> */
    public function headers(): array
    {
        $choices = array_map(fn (int $i): string => "choice_{$i}", range(1, $this->maxChoices()));

        return ['question', ...$choices, 'correct', 'mark', 'explanation'];
    }

    public function maxChoices(): int
    {
        return (int) config('exams.import.max_choices', 6);
    }

    /**
     * @return array{headers: list<string>, rows: array<int, array<string, string>>} رقم الصف في الملف (الهيدر = ١) ← القيم
     */
    public function read(string $contents): array
    {
        $contents = $this->toUtf8($contents);
        $reader = Reader::fromString($contents);
        $reader->setDelimiter($this->delimiter($contents));

        $headers = [];
        $rows = [];
        $line = 0;

        foreach ($reader->getRecords() as $record) {
            $line++;
            $record = array_map(fn (mixed $v): string => trim((string) $v), $record);

            if ($line === 1) {
                $headers = array_map(fn (string $h): string => mb_strtolower(str_replace([' ', '-'], '_', $h)), $record);

                continue;
            }

            if (implode('', $record) === '') {
                continue; // سطر فاضي في الآخر — Excel بيسيبه كتير
            }

            $row = [];

            foreach ($headers as $index => $header) {
                $row[$header] = $record[$index] ?? '';
            }

            $rows[$line] = $row;
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /** قالب فاضي بأمثلة عربي — بـ BOM عشان Excel يفتح العربي صح */
    public function template(): string
    {
        $writer = Writer::fromString();
        $writer->insertOne($this->headers());

        foreach ((array) __('exams::exams.import.samples') as $sample) {
            $sample = (array) $sample;
            $choices = array_pad(array_slice((array) ($sample['choices'] ?? []), 0, $this->maxChoices()), $this->maxChoices(), '');

            $writer->insertOne([
                (string) ($sample['question'] ?? ''),
                ...$choices,
                (string) ($sample['correct'] ?? ''),
                (string) ($sample['mark'] ?? ''),
                (string) ($sample['explanation'] ?? ''),
            ]);
        }

        return "\u{FEFF}".$writer->toString();
    }

    private function toUtf8(string $contents): string
    {
        if (str_starts_with($contents, "\u{FEFF}")) {
            $contents = substr($contents, 3);
        }

        if (mb_check_encoding($contents, 'UTF-8')) {
            return $contents;
        }

        $converted = function_exists('iconv') ? @iconv('CP1256', 'UTF-8//IGNORE', $contents) : false;

        return is_string($converted) ? $converted : mb_convert_encoding($contents, 'UTF-8', 'ISO-8859-1');
    }

    private function delimiter(string $contents): string
    {
        $first = strtok($contents, "\n") ?: '';
        $counts = [',' => substr_count($first, ','), ';' => substr_count($first, ';'), "\t" => substr_count($first, "\t")];
        arsort($counts);

        return (string) array_key_first($counts);
    }
}
