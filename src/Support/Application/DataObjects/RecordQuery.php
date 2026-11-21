<?php

declare(strict_types=1);

namespace Src\Support\Application\DataObjects;

/**
 * معايير صفحة من سجلات — كل عمود هنا جاي من قائمة بيضاء في إعلان المورد،
 * مش من اسم عمود حر في المدخل.
 */
final readonly class RecordQuery
{
    /**
     * @param list<string> $searchColumns أعمدة البحث (المترجمة بتتبحث في كل اللغات)
     * @param list<string> $translatableColumns
     * @param array<string, mixed> $filters [عمود => قيمة] تطابق تام
     */
    public function __construct(
        public ?string $search = null,
        public array $searchColumns = [],
        public array $translatableColumns = [],
        public array $filters = [],
        public ?string $sortColumn = null,
        public string $sortDirection = 'desc',
        public ?string $trashed = null,
        public int $limit = 20,
        public ?string $cursor = null,
    ) {}
}
