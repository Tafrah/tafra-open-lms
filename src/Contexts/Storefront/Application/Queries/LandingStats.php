<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Application\Queries;

use Illuminate\Support\Facades\Cache;
use Src\Contexts\Academy\Domain\Models\OnlineLesson;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Application\Contracts\TenantContext;

/** أرقام الصفحة الرئيسية (طلبة/دروس/امتحانات) — متخزّنة كام دقيقة لكل مؤسسة. */
final readonly class LandingStats
{
    /** @return array{students: int, lessons: int, exams: int} */
    public function get(): array
    {
        /** @var array{students: int, lessons: int, exams: int} */
        return Cache::remember(self::cacheKey(), (int) config('storefront.stats_cache_seconds', 300), fn (): array => [
            'students' => Student::query()->count(),
            'lessons' => OnlineLesson::query()->count(),
            'exams' => OnlineExam::query()->where('published', true)->count(),
        ]);
    }

    public static function cacheKey(): string
    {
        return 'storefront:stats:'.(app(TenantContext::class)->id() ?? 0);
    }
}
