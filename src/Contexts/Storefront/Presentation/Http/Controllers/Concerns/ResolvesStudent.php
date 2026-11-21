<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Storefront\Application\Queries\CourseCatalog;
use Src\Contexts\Students\Domain\Models\Student;

trait ResolvesStudent
{
    protected function student(): Student
    {
        $student = Auth::guard('student')->user();

        abort_unless($student instanceof Student, 403);

        return $student;
    }

    protected function optionalStudent(): ?Student
    {
        $student = Auth::guard('student')->user();

        return $student instanceof Student ? $student : null;
    }

    protected function publishedCourse(string $slug): Bootcamp
    {
        $course = app(CourseCatalog::class)->findPublished($slug);

        abort_if($course === null, 404);

        return $course;
    }
}
