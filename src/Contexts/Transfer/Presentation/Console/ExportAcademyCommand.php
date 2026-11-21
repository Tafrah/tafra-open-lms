<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer\Presentation\Console;

use Illuminate\Console\Command;
use Src\Contexts\Transfer\Application\Actions\ExportAcademyAction;
use Src\Support\Domain\Exceptions\DomainException;

final class ExportAcademyCommand extends Command
{
    use ResolvesAcademy;

    protected $signature = 'tafra:export
                            {--path= : مكان ملف الـ zip (الافتراضي storage/app/exports)}
                            {--academy= : رقم الأكاديمية (لو فيه أكتر من واحدة)}';

    protected $description = 'تصدير الأكاديمية كاملة بصيغة طفرة (zip)';

    public function handle(ExportAcademyAction $export): int
    {
        $tenant = $this->enterAcademy();

        if ($tenant === null) {
            return self::FAILURE;
        }

        $path = (string) ($this->option('path') ?: storage_path('app/exports/tafra-export-'.now()->format('Y-m-d-His').'.zip'));

        try {
            $export->handle($path);
        } catch (DomainException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(__('transfer::transfer.console.exported', ['path' => $path]));

        return self::SUCCESS;
    }
}
