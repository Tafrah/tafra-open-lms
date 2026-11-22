<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * جهاز دخل منه الطالب. التوكن نفسه في كوكي عند الطالب؛ هنا الـ hash بس.
 * المدرس بيشوف الأجهزة ويفصلها كلها من شاشة الطالب في اللوحة.
 */
final class StudentDevice extends Model
{
    use BelongsToTenant;

    protected $table = 'student_devices';

    /** @var list<string> */
    protected $fillable = [
        'student_id',
        'token_hash',
        'device_name',
        'user_agent',
        'ip_address',
        'last_seen_at',
    ];

    /** @var list<string> */
    protected $hidden = ['token_hash'];

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
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
            'last_seen_at' => 'datetime',
        ];
    }
}
