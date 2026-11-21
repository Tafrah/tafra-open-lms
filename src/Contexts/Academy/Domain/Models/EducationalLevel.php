<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Src\Contexts\Academy\Database\Factories\EducationalLevelFactory;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * الصف الدراسي (أولى/تانية/تالتة ثانوي…) — `educational_levels` في طفرة.
 */
final class EducationalLevel extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<EducationalLevelFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'educational_levels';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'code',
        'color',
        'order',
    ];

    /** @return HasMany<Bootcamp, $this> */
    public function bootcamps(): HasMany
    {
        return $this->hasMany(Bootcamp::class);
    }

    /** @return HasMany<Student, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    protected static function newFactory(): EducationalLevelFactory
    {
        return EducationalLevelFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }
}
