<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * استثناء لطالب واحد: مشاهدات زيادة على حد العنصر (`allowed_views_number`).
 */
final class StudentItemViewGrant extends Model
{
    use BelongsToTenant;

    protected $table = 'student_item_view_grants';

    /** @var list<string> */
    protected $fillable = [
        'student_id',
        'bootcamp_section_item_id',
        'extra_views',
        'granted_by',
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

    /** @return BelongsTo<User, $this> */
    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'extra_views' => 'integer',
        ];
    }
}
