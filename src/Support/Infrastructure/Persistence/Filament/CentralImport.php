<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Persistence\Filament;

use Filament\Actions\Imports\Models\Import;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * موديل الاستيراد بتاع Filament على الاتصال المركزي — نفس سبب `CentralExport`.
 *
 * `Import::failedRows()` بيحلّ `app(FailedImportRow::class)`، فالعلاقة
 * بتروح لـ `CentralFailedImportRow` تلقائياً بعد الربط.
 */
final class CentralImport extends Import
{
    use CentralConnection;

    /** الاسم صريح: من غيره Eloquent بيخمّن الجدول من اسم الكلاس (`central_imports`) */
    protected $table = 'imports';
}
