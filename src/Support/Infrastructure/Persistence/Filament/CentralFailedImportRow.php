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

    // اسم الجدول بيتشتق من اسم الكلاس (`central_failed_import_rows`) — لازم يتثبت على جدول Filament الأصلي
    protected $table = 'failed_import_rows';
}
