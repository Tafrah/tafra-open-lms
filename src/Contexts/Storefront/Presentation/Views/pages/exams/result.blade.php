@extends('storefront::layouts.student')

@section('title', __('storefront::storefront.exams.result_title', ['exam' => $exam->name]))

@section('content')
    @php
        $passed = $attempt->passed();
        $course = $item?->section?->bootcamp;
    @endphp
    <div class="mx-auto max-w-4xl space-y-6">
        <section class="space-y-6 rounded-banner bg-primary/5 p-6 sm:p-8" aria-labelledby="result-title">
            <div class="text-center">
                <p class="text-sm font-bold text-primary">{{ $exam->name }}</p>
                <h1 id="result-title" class="mt-1 text-2xl font-extrabold text-navy">{{ __('storefront::storefront.exams.your_result') }}</h1>
            </div>

            <div class="flex flex-col items-center gap-2">
                <p class="text-5xl font-extrabold text-navy" dir="ltr">{{ fc_number((float) $attempt->student_total_mark, 1) }} <span class="text-2xl text-body">/ {{ fc_number((float) $attempt->max_total_mark, 1) }}</span></p>
                <p class="text-lg font-bold text-body">{{ fc_number((float) $attempt->student_percentage) }}%</p>
                @if ($pending)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-warning/15 px-4 py-1.5 font-bold text-warning-ink"><x-storefront::icon name="clock" class="size-4" /> {{ __('storefront::storefront.exams.awaiting_essay') }}</span>
                @elseif ($passed)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-success/15 px-4 py-1.5 font-bold text-success-ink"><x-storefront::icon name="check-circle" class="size-4" /> {{ __('storefront::storefront.exams.states.passed') }}</span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-danger/15 px-4 py-1.5 font-bold text-danger-ink"><x-storefront::icon name="x-circle" class="size-4" /> {{ __('storefront::storefront.exams.states.failed') }}</span>
                @endif
            </div>

            <dl class="grid grid-cols-3 gap-3">
                @foreach ([['correct', 'text-success-ink'], ['wrong', 'text-danger-ink'], ['unanswered', 'text-body']] as [$key, $tone])
                    <div class="rounded-2xl bg-surface p-4 text-center shadow-sm">
                        <dd class="text-5xl font-extrabold {{ $tone }}">{{ fc_number($counts[$key]) }}</dd>
                        <dt class="mt-1 text-xs font-bold text-body sm:text-sm">{{ __('storefront::storefront.exams.counts.'.$key) }}</dt>
                    </div>
                @endforeach
            </dl>

            <div class="flex flex-wrap justify-center gap-3">
                @if ($course && $item)
                    <a href="{{ route('site.learn', [$course->slug, $item->getKey()]) }}" class="sf-btn-primary">{{ __('storefront::storefront.exams.back_to_course') }}</a>
                    <a href="{{ route('site.exams.start', $item->getKey()) }}" class="sf-btn-outline">{{ __('storefront::storefront.exams.try_again') }}</a>
                @endif
                <a href="{{ route('site.exams') }}" class="sf-btn-outline">{{ __('storefront::storefront.nav.exams') }}</a>
            </div>
        </section>

        @if ($showAnswers)
            <section class="space-y-4" aria-labelledby="review-title">
                <h2 id="review-title" class="text-lg font-bold text-navy">{{ __('storefront::storefront.exams.review') }}</h2>
                @foreach ($questions as $index => $question)
                    @php
                        $answer = $answers->get($question->getKey());
                        $chosen = $answer?->answer['choice_id'] ?? null;
                        $isMcq = $question->type === \Src\Contexts\Exams\Domain\Enums\QuestionType::MultipleChoice;
                    @endphp
                    <article class="sf-card space-y-4 p-5" aria-labelledby="rq-{{ $question->getKey() }}">
                        <div class="flex items-start justify-between gap-3">
                            <h3 id="rq-{{ $question->getKey() }}" class="text-sm font-bold text-primary">{{ __('storefront::storefront.exams.question_n', ['n' => $index + 1]) }}</h3>
                            <span class="text-xs font-bold text-body" dir="ltr">{{ fc_number((float) ($answer?->student_awarded_mark ?? 0), 1) }} / {{ fc_number((float) $question->mark, 1) }}</span>
                        </div>
                        <div class="sf-prose">{!! app(\Src\Support\Infrastructure\Html\HtmlSanitizer::class)->clean($question->text) !!}</div>
                        @if ($isMcq)
                            <ul class="space-y-2">
                                @foreach ($question->choices as $choice)
                                    @php
                                        $mine = $chosen !== null && (int) $chosen === (int) $choice->getKey();
                                        $tone = $choice->is_correct ? 'border-success bg-success text-white' : ($mine ? 'border-danger bg-danger text-white' : 'border-line bg-surface text-ink');
                                    @endphp
                                    <li class="flex items-center gap-3 rounded-lg border-2 p-4 font-bold {{ $tone }}">
                                        <span class="flex-1">{{ $choice->text }}</span>
                                        @if ($choice->is_correct)<span class="text-xs">{{ __('storefront::storefront.exams.correct_answer') }}</span>@endif
                                        @if ($mine && ! $choice->is_correct)<span class="text-xs">{{ __('storefront::storefront.exams.your_choice') }}</span>@endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="rounded-lg bg-surface-alt p-4 text-sm leading-7 whitespace-pre-line text-ink">{{ trim((string) ($answer?->answer['text'] ?? '')) ?: __('storefront::storefront.exams.no_answer') }}</div>
                            @if ($answer?->grading_status === \Src\Contexts\Exams\Domain\Enums\GradingStatus::Pending)
                                <p class="text-sm font-bold text-warning-ink">{{ __('storefront::storefront.exams.awaiting_essay') }}</p>
                            @elseif ($answer?->grader_feedback)
                                <p class="text-sm text-body">{{ __('storefront::storefront.exams.feedback', ['feedback' => $answer->grader_feedback]) }}</p>
                            @endif
                        @endif
                        @if ($showExplanation && filled($question->explanation_text))
                            <div class="sf-prose rounded-lg bg-gray-50 p-4 text-sm dark:bg-surface-alt">
                                <p class="font-bold text-navy">{{ __('storefront::storefront.exams.explanation') }}</p>
                                {!! app(\Src\Support\Infrastructure\Html\HtmlSanitizer::class)->clean($question->explanation_text) !!}
                            </div>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif
    </div>
@endsection
