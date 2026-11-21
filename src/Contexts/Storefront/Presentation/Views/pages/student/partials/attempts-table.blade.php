@if ($attempts->isEmpty())
    <x-storefront::empty icon="exam" :title="__('storefront::storefront.empty.results_title')" :body="__('storefront::storefront.empty.results_body')" />
@else
    <div class="sf-card overflow-x-auto">
        <table class="w-full min-w-[36rem] text-sm">
            <caption class="sr-only">{{ __('storefront::storefront.nav.exams') }}</caption>
            <thead class="bg-primary/5 text-body">
                <tr>
                    <th scope="col" class="px-4 py-3 text-start font-bold">{{ __('storefront::storefront.exams.exam') }}</th>
                    <th scope="col" class="px-4 py-3 text-start font-bold">{{ __('storefront::storefront.exams.score') }}</th>
                    <th scope="col" class="px-4 py-3 text-start font-bold">{{ __('storefront::storefront.exams.status') }}</th>
                    <th scope="col" class="px-4 py-3 text-start font-bold">{{ __('storefront::storefront.exams.date') }}</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('storefront::storefront.actions.view') }}</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attempts as $attempt)
                    @php
                        $state = ! $attempt->finished ? 'open' : ($attempt->grading_status === \Src\Contexts\Exams\Domain\Enums\GradingStatus::Pending ? 'pending' : ($attempt->passed() ? 'passed' : 'failed'));
                    @endphp
                    <tr class="border-t border-dashed border-line">
                        <td class="px-4 py-3 font-bold text-ink">{{ $attempt->exam?->name }}</td>
                        <td class="px-4 py-3 text-body" dir="ltr">{{ $attempt->finished ? fc_number((float) $attempt->student_total_mark, 1).' / '.fc_number((float) $attempt->max_total_mark, 1).' ('.fc_number((float) $attempt->student_percentage).'%)' : '—' }}</td>
                        <td class="px-4 py-3"><x-storefront::status-badge :status="$state">{{ __('storefront::storefront.exams.states.'.$state) }}</x-storefront::status-badge></td>
                        <td class="px-4 py-3 text-body">{{ fc_date($attempt->start_at, 'd M Y') }}</td>
                        <td class="px-4 py-3 text-end">
                            <a href="{{ $attempt->finished ? route('site.attempts.result', $attempt->getKey()) : route('site.attempts.show', $attempt->getKey()) }}" class="font-bold text-primary hover:underline">{{ $attempt->finished ? __('storefront::storefront.exams.view_result') : __('storefront::storefront.exams.resume') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
