@php $total = count($questions); @endphp
<div class="mx-auto max-w-4xl space-y-5"
     x-data="{
        remaining: @js($remaining),
        timeUp: false,
        confirm: false,
        tick() {
            if (this.remaining === null) return;
            const timer = setInterval(() => {
                this.remaining = Math.max(0, this.remaining - 1);
                if (this.remaining === 0) { clearInterval(timer); this.timeUp = true; $wire.submit(); }
            }, 1000);
        },
        clock() {
            if (this.remaining === null) return '';
            const h = Math.floor(this.remaining / 3600), m = Math.floor((this.remaining % 3600) / 60), s = this.remaining % 60;
            return (h > 0 ? String(h).padStart(2, '0') + ':' : '') + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        },
     }"
     x-init="tick()">

    {{-- الهيدر: العنوان، شريط اللي اتجاوب، العداد --}}
    <header class="sf-card sticky top-2 z-20 space-y-3 p-4 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <h1 class="min-w-0 truncate text-lg font-bold text-navy">{{ $exam?->name }}</h1>
            @if ($remaining !== null)
                <p class="flex shrink-0 items-center gap-1.5 text-2xl font-bold tabular-nums" :class="remaining <= 60 ? 'text-danger animate-pulse' : 'text-ink'" dir="ltr" role="timer" aria-live="off">
                    <x-storefront::icon name="clock" class="size-6" />
                    <span x-text="clock()"></span>
                </p>
            @endif
        </div>
        <div>
            <div class="flex justify-between text-xs font-bold text-body">
                <span>{{ __('storefront::storefront.exams.answered', ['done' => fc_number($answered), 'total' => fc_number($total)]) }}</span>
                <span>{{ fc_number($total > 0 ? round($answered / $total * 100) : 0) }}%</span>
            </div>
            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-surface-alt">
                <div class="h-full rounded-full bg-primary transition-all" style="width: {{ $total > 0 ? round($answered / $total * 100) : 0 }}%"></div>
            </div>
        </div>
    </header>

    {{-- دواير الأسئلة --}}
    <nav class="flex flex-wrap gap-2" aria-label="{{ __('storefront::storefront.exams.navigator') }}">
        @foreach ($questions as $index => $q)
            @php
                $isAnswered = $q['type'] === \Src\Contexts\Exams\Domain\Enums\QuestionType::MultipleChoice ? ($choices[$q['id']] ?? null) !== null : trim((string) ($texts[$q['id']] ?? '')) !== '';
                $isFlagged = in_array($q['id'], $flagged, true);
            @endphp
            <button type="button" wire:click="goTo({{ $index }})" @if ($index === $current) aria-current="step" @endif
                    aria-label="{{ __('storefront::storefront.exams.question_n', ['n' => $index + 1]) }}{{ $isAnswered ? ' — '.__('storefront::storefront.exams.answered_short') : '' }}{{ $isFlagged ? ' — '.__('storefront::storefront.exams.flagged') : '' }}"
                    class="relative grid size-8 place-items-center rounded-full border-2 text-xs font-bold transition
                        {{ $index === $current ? 'border-primary bg-primary text-white' : ($isAnswered ? 'border-primary/40 bg-primary/15 text-primary' : 'border-line bg-surface text-body hover:border-primary') }}">
                {{ $index + 1 }}
                @if ($isFlagged)<span class="absolute -end-1 -top-1 size-3 rounded-full border-2 border-surface bg-warning" aria-hidden="true"></span>@endif
            </button>
        @endforeach
    </nav>

    {{-- السؤال --}}
    @if ($question)
        <section class="sf-card space-y-5 p-5 sm:p-7" wire:key="q-{{ $question['id'] }}" aria-labelledby="q-title">
            <div class="flex items-start justify-between gap-3">
                <h2 id="q-title" class="text-sm font-bold text-primary">{{ __('storefront::storefront.exams.question_of', ['n' => fc_number($current + 1), 'total' => fc_number($total)]) }} · {{ trans_choice('storefront::storefront.counts.marks', (int) ceil($question['mark']), ['count' => fc_number($question['mark'], $question['mark'] == floor($question['mark']) ? 0 : 1)]) }}</h2>
                <button type="button" wire:click="toggleFlag({{ $question['id'] }})" class="flex items-center gap-1 text-xs font-bold {{ in_array($question['id'], $flagged, true) ? 'text-warning-ink' : 'text-body hover:text-warning-ink' }}" aria-pressed="{{ in_array($question['id'], $flagged, true) ? 'true' : 'false' }}">
                    <x-storefront::icon name="flag" class="size-4" /> {{ __('storefront::storefront.exams.flag') }}
                </button>
            </div>
            <div class="sf-prose text-lg">{!! app(\Src\Support\Infrastructure\Html\HtmlSanitizer::class)->clean($question['text']) !!}</div>
            @if ($question['image'])
                <img src="{{ $question['image'] }}" alt="" class="max-h-80 rounded-card border border-line">
            @endif

            @if ($question['type'] === \Src\Contexts\Exams\Domain\Enums\QuestionType::MultipleChoice)
                <fieldset class="space-y-3">
                    <legend class="sr-only">{{ __('storefront::storefront.exams.choose_answer') }}</legend>
                    @foreach ($question['choices'] as $choice)
                        @php $selected = ($choices[$question['id']] ?? null) === $choice['id']; @endphp
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border-2 p-4 font-bold transition {{ $selected ? 'border-primary bg-primary text-white' : 'border-line bg-surface text-ink hover:border-primary/60' }}">
                            <input type="radio" name="q{{ $question['id'] }}" value="{{ $choice['id'] }}" class="sr-only" @checked($selected)
                                   wire:click="select({{ $question['id'] }}, {{ $choice['id'] }})" wire:loading.attr="disabled">
                            <span class="grid size-6 shrink-0 place-items-center rounded-full border-2 {{ $selected ? 'border-white' : 'border-line' }}" aria-hidden="true">
                                @if ($selected)<span class="size-2.5 rounded-full bg-white"></span>@endif
                            </span>
                            <span>{{ $choice['text'] }}</span>
                        </label>
                    @endforeach
                </fieldset>
            @else
                <div>
                    <label for="essay-{{ $question['id'] }}" class="sf-label">{{ __('storefront::storefront.exams.your_answer') }}</label>
                    <textarea id="essay-{{ $question['id'] }}" rows="7" maxlength="10000" class="sf-input leading-7" wire:model.live.debounce.1000ms="texts.{{ $question['id'] }}" placeholder="{{ __('storefront::storefront.exams.essay_placeholder') }}"></textarea>
                    <p class="mt-1 text-xs text-muted" wire:loading wire:target="texts.{{ $question['id'] }}">{{ __('storefront::storefront.exams.saving') }}</p>
                </div>
            @endif

            <div class="flex items-center justify-between gap-3 border-t border-line pt-4">
                <button type="button" class="sf-btn-outline rounded-xl" wire:click="goTo({{ $current - 1 }})" @disabled($current === 0)><x-storefront::icon name="chevron-prev" class="size-4" /> {{ __('storefront::storefront.learn.previous') }}</button>
                @if ($current < $total - 1)
                    <button type="button" class="sf-btn-primary rounded-xl" wire:click="goTo({{ $current + 1 }})">{{ __('storefront::storefront.learn.next') }} <x-storefront::icon name="chevron-next" class="size-4" /></button>
                @else
                    <button type="button" class="sf-btn-primary rounded-xl" x-on:click="confirm = true">{{ __('storefront::storefront.exams.submit') }}</button>
                @endif
            </div>
        </section>
    @endif

    <div class="flex justify-center">
        <button type="button" class="sf-btn-outline" x-on:click="confirm = true">{{ __('storefront::storefront.exams.submit') }}</button>
    </div>

    {{-- تأكيد التسليم --}}
    <div x-show="confirm" x-cloak class="fixed inset-0 z-[70] grid place-items-center p-4" role="dialog" aria-modal="true" aria-labelledby="confirm-title" x-on:keydown.escape.window="confirm = false">
        <div class="absolute inset-0 bg-black/50" x-on:click="confirm = false"></div>
        <div class="relative w-full max-w-md space-y-4 rounded-banner bg-surface p-6 text-center shadow-2xl" x-trap="confirm">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-primary/10 text-primary"><x-storefront::icon name="exam" class="size-8" /></span>
            <h2 id="confirm-title" class="text-lg font-bold text-navy">{{ __('storefront::storefront.exams.confirm_title') }}</h2>
            <p class="text-sm text-body">
                {{ __('storefront::storefront.exams.confirm_body', ['done' => fc_number($answered), 'total' => fc_number($total)]) }}
                @if ($answered < $total)<span class="mt-1 block font-bold text-warning-ink">{{ __('storefront::storefront.exams.confirm_unanswered', ['count' => fc_number($total - $answered)]) }}</span>@endif
            </p>
            <div class="flex gap-3">
                <button type="button" class="sf-btn-primary flex-1" wire:click="submit" wire:loading.attr="disabled" wire:target="submit">{{ __('storefront::storefront.exams.submit_now') }}</button>
                <button type="button" class="sf-btn-outline flex-1" x-on:click="confirm = false">{{ __('storefront::storefront.exams.keep_going') }}</button>
            </div>
        </div>
    </div>

    {{-- الوقت خلص --}}
    <div x-show="timeUp" x-cloak class="fixed inset-0 z-[80] grid place-items-center p-4" role="alertdialog" aria-modal="true" aria-labelledby="timeup-title">
        <div class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-sm space-y-3 rounded-banner bg-surface p-6 text-center shadow-2xl">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-danger/15 text-danger"><x-storefront::icon name="clock" class="size-8" /></span>
            <h2 id="timeup-title" class="text-lg font-bold text-navy">{{ __('storefront::storefront.exams.time_up_title') }}</h2>
            <p class="text-sm text-body">{{ __('storefront::storefront.exams.time_up_body') }}</p>
        </div>
    </div>
</div>
