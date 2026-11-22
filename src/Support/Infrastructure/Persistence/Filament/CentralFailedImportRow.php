<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Persistence\Filament;

use Filament\Actions\Imports\Models\FailedImportRow;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * صفوف الاستيراد الفاشلة على الاتصال المركزي — نفس سبب `CentralExport`.
 */
final class CentralFailedImportRow extends FailedImportRow
{
    use CentralConnection;

    /** الاسم صريح: من غيره Eloquent بيخمّن الجدول من اسم الكلاس (`central_failed_import_rows`) */
    protected $table = 'failed_import_rows';
}
