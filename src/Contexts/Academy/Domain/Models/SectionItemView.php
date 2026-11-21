<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * تقدّم الطالب في عنصر (عدد المشاهدات + خلّصه ولا لأ).
 */
final class SectionItemView extends Model
{
    use BelongsToTenant;

    protected $table = 'section_item_views';

    /** @var list<string> */
    protected $fillable = [
        'bootcamp_section_item_id',
        'student_id',
        'views_count',
        'is_completed',
        'viewed_at',
        'completed_at',
    ];

    /** @return BelongsTo<BootcampSectionItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(BootcampSectionItem::class, 'bootcamp_section_item_id');
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'viewed_at' => 'datetime',
            'completed_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }
}
