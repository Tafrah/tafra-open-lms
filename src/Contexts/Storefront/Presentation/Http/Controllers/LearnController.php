<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Src\Contexts\Academy\Application\Actions\RecordItemViewAction;
use Src\Contexts\Academy\Domain\Enums\LessonType;
use Src\Contexts\Academy\Domain\Enums\SectionItemType;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Domain\Models\BootcampSection;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Storefront\Application\Queries\CourseOutline;
use Src\Contexts\Storefront\Presentation\Http\Controllers\Concerns\ResolvesStudent;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * مشغّل الدرس (شاشة كاملة برا لوحة الطالب). كل عنصر بيعدّي على
 * `ItemAccess` — القفل في السايدبار UX، والمنع الفعلي هنا.
 */
final class LearnController
{
    use ResolvesStudent;

    public function __construct(
        private readonly CourseOutline $outline,
        private readonly RecordItemViewAction $views,
    ) {}

    /** أول عنصر مفتوح في الكورس */
    public function course(string $slug): RedirectResponse
    {
        $course = $this->publishedCourse($slug);
        $sections = $this->outline->sections($course);
        $states = $this->outline->states($sections, $this->student());

        foreach ($this->outline->flatItems($sections) as $item) {
            if ($states[(int) $item->getKey()]['denial'] === null && ! $states[(int) $item->getKey()]['completed']) {
                return redirect()->route('site.learn', [$course->slug, $item->getKey()]);
            }
        }

        foreach ($this->outline->flatItems($sections) as $item) {
            if ($states[(int) $item->getKey()]['denial'] === null) {
                return redirect()->route('site.learn', [$course->slug, $item->getKey()]);
            }
        }

        return redirect()->route('site.courses.show', $course->slug)
            ->with('error', __('academy::academy.errors.not_enrolled'));
    }

    public function show(string $slug, int $item): Response
    {
        $student = $this->student();
        [$course, $sections, $items, $current] = $this->locate($slug, $item);
        $states = $this->outline->states($sections, $student);
        $denial = $states[$item]['denial'];

        if ($denial === null && $current->type === SectionItemType::Lesson) {
            $this->views->handle($student, $current);
        }

        $index = array_search($current, $items, true);
        $completed = count(array_filter($states, fn (array $s): bool => $s['completed']));
        $lesson = $current->lesson;

        $documentUrl = null;

        if ($denial === null && $lesson !== null && $lesson->type === LessonType::Pdf && $lesson->getFirstMedia('documents') !== null) {
            $documentUrl = URL::temporarySignedRoute(
                'site.learn.document',
                now()->addMinutes((int) config('storefront.pdf_link_minutes', 30)),
                [$course->slug, $current->getKey()],
            );
        }

        return response()->view('storefront::pages.learn.player', [
            'course' => $course,
            'sections' => $sections,
            'states' => $states,
            'current' => $current,
            'lesson' => $lesson,
            'denial' => $denial,
            'previous' => is_int($index) && $index > 0 ? $items[$index - 1] : null,
            'next' => is_int($index) && $index < count($items) - 1 ? $items[$index + 1] : null,
            'progress' => $items === [] ? 0 : (int) round($completed / count($items) * 100),
            'documentUrl' => $documentUrl,
            'youtubeId' => $denial === null && $lesson?->type === LessonType::YoutubeVideo ? $lesson->youtubeId() : null,
        ], $denial === null ? 200 : 403);
    }

    public function complete(string $slug, int $item): RedirectResponse
    {
        $student = $this->student();
        [$course, $sections, $items, $current] = $this->locate($slug, $item);

        abort_unless($current->type === SectionItemType::Lesson, 404);
        abort_if($this->outline->states($sections, $student)[$item]['denial'] !== null, 403);

        $this->views->complete($student, $current);

        $index = array_search($current, $items, true);
        $next = is_int($index) ? ($items[$index + 1] ?? null) : null;

        return redirect()
            ->route('site.learn', [$course->slug, ($next ?? $current)->getKey()])
            ->with('status', __('storefront::storefront.learn.completed'));
    }

    /** ملف الـ PDF: رابط موقّع **و** الطالب لسه مسموحله — مفيش رابط عام للملف */
    public function document(string $slug, int $item): StreamedResponse
    {
        $student = $this->student();
        [, $sections, , $current] = $this->locate($slug, $item);

        abort_if($this->outline->states($sections, $student)[$item]['denial'] !== null, 403);

        $media = $current->lesson?->type === LessonType::Pdf ? $current->lesson->getFirstMedia('documents') : null;
        abort_if($media === null, 404);

        $disk = Storage::disk($media->disk);
        $path = $media->getPathRelativeToRoot();
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, $media->file_name, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex',
        ], 'inline');
    }

    /**
     * @return array{0: Bootcamp, 1: Collection<int, BootcampSection>, 2: list<BootcampSectionItem>, 3: BootcampSectionItem}
     */
    private function locate(string $slug, int $itemId): array
    {
        $course = $this->publishedCourse($slug);
        $sections = $this->outline->sections($course);
        $items = $this->outline->flatItems($sections);

        $current = collect($items)->first(fn (BootcampSectionItem $i): bool => (int) $i->getKey() === $itemId);
        abort_if($current === null, 404);

        return [$course, $sections, $items, $current];
    }
}
