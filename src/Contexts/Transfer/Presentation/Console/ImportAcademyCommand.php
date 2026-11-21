<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer\Presentation\Console;

use Illuminate\Console\Command;
use Src\Contexts\Transfer\Application\Actions\ImportAcademyAction;
use Src\Contexts\Transfer\Application\DataObjects\ImportReport;
use Src\Support\Domain\Exceptions\DomainException;

final class ImportAcademyCommand extends Command
{
    use ResolvesAcademy;

    protected $signature = 'tafra:import
                            {path : ملف الـ zip}
                            {--dry-run : تجربة من غير ما حاجة تتحفظ}
                            {--academy= : رقم الأكاديمية (لو فيه أكتر من واحدة)}';

    protected $description = 'استيراد أكاديمية من ملف تصدير (المنصة المجانية أو طفرة)';

    public function handle(ImportAcademyAction $import): int
    {
        if ($this->enterAcademy() === null) {
            return self::FAILURE;
        }

        try {
            $report = $import->handle((string) $this->argument('path'), (bool) $this->option('dry-run'));
        } catch (DomainException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->render($report);

        return self::SUCCESS;
    }

    private function render(ImportReport $report): void
    {
        $this->table(
            [__('transfer::transfer.console.table'), __('transfer::transfer.console.read'), __('transfer::transfer.console.imported'), __('transfer::transfer.console.merged'), __('transfer::transfer.console.skipped')],
            collect($report->tables)->map(fn (array $c, string $t): array => [$t, $c['read'], $c['imported'], $c['merged'], $c['skipped']])->values()->all(),
        );

        foreach ($report->warnings as $warning) {
            $this->components->warn($warning);
        }

        $this->components->info($report->dryRun
            ? __('transfer::transfer.console.dry_run_done')
            : __('transfer::transfer.console.imported_done', ['media' => $report->media]));
    }
}
