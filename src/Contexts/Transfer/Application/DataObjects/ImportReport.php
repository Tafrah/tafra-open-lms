<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer\Application\DataObjects;

/** نتيجة الاستيراد (أو التجربة): لكل جدول اتقرا كام، اتضاف كام، اتدمج كام، اتساب كام ولية. */
final class ImportReport
{
    /** @var array<string, array{read: int, imported: int, merged: int, skipped: int}> */
    public array $tables = [];

    /** @var list<string> */
    public array $warnings = [];

    public int $media = 0;

    public function __construct(public readonly bool $dryRun) {}

    public function read(string $table): void
    {
        $this->bump($table, 'read');
    }

    public function imported(string $table): void
    {
        $this->bump($table, 'imported');
    }

    public function merged(string $table): void
    {
        $this->bump($table, 'merged');
    }

    /** @param array<string, string|int|float> $replace */
    public function skipped(string $table, string $reasonKey, array $replace = []): void
    {
        $this->bump($table, 'skipped');
        $message = __("transfer::transfer.warnings.{$reasonKey}", ['table' => $table, ...$replace]);

        if (! in_array($message, $this->warnings, true) && count($this->warnings) < 200) {
            $this->warnings[] = $message;
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['dry_run' => $this->dryRun, 'tables' => $this->tables, 'media' => $this->media, 'warnings' => $this->warnings];
    }

    private function bump(string $table, string $key): void
    {
        $this->tables[$table] ??= ['read' => 0, 'imported' => 0, 'merged' => 0, 'skipped' => 0];
        $this->tables[$table][$key]++;
    }
}
