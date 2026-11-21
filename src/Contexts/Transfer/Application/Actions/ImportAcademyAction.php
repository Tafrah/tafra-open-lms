<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer\Application\Actions;

use Src\Contexts\Transfer\Application\DataObjects\ImportReport;
use Src\Contexts\Transfer\Application\Services\AcademyImporter;

/**
 * استيراد ملف تصدير في الأكاديمية الحالية (التفاصيل في AcademyImporter).
 * كل استيراد بياخد نسخة جديدة من المستورد — الخرايط ماتتشاركش بين عمليتين.
 */
final readonly class ImportAcademyAction
{
    public function handle(string $zipPath, bool $dryRun = false, ?int $actorId = null): ImportReport
    {
        return app(AcademyImporter::class)->run($zipPath, $dryRun, $actorId);
    }
}
