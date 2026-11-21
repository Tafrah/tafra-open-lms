<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Src\Contexts\Academy\Domain\Enums\SectionItemType;
use Src\Contexts\Academy\Domain\Models\OnlineLesson;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Storefront\Application\Queries\CourseCatalog;
use Src\Contexts\Storefront\Application\Queries\CourseOutline;
use Src\Contexts\Storefront\Presentation\Http\Controllers\Concerns\ResolvesStudent;

final class CourseController
{
    use ResolvesStudent;

    public function __construct(
        private readonly CourseCatalog $catalog,
        private readonly CourseOutline $outline,
        private readonly BootcampAccess $access,
    ) {}

    public function index(Request $request): View
    {
        $levels = $this->catalog->levels();
        $levelId = filter_var($request->query('level'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $activeLevel = $levelId !== false ? $levels->firstWhere('id', $levelId) : null;
        $term = is_string($request->query('q')) ? trim(mb_substr($request->query('q'), 0, 100)) : '';

        return view('storefront::pages.courses', [
            'levels' => $levels,
            'activeLevel' => $activeLevel,
            'term' => $term,
            'courses' => $this->catalog->search($activeLevel?->getKey(), $term !== '' ? $term : null, (int) config('storefront.per_page', 12)),
        ]);
    }

    public function show(string $slug): View
    {
        $course = $this->publishedCourse($slug);
        $student = $this->optionalStudent();
        $sections = $this->outline->sections($course);

        return view('storefront::pages.course', [
            'course' => $course,
            'sections' => $sections,
            'student' => $student,
            'enrolled' => $student !== null && $this->access->has($student, $course),
            'lessonsCount' => $this->outline->countOf($sections, SectionItemType::Lesson),
            'examsCount' => $this->outline->countOf($sections, SectionItemType::Exam),
            'promoId' => filled($course->promotional_video_url)
                ? (new OnlineLesson)->forceFill(['external_url' => $course->promotional_video_url])->youtubeId()
                : null,
        ]);
    }
}
