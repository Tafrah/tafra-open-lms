@extends('installer::layout')

@section('title', __('installer::installer.requirements.title'))
@section('lead', __('installer::installer.requirements.lead'))

@section('content')
    @foreach ($report->grouped() as $group => $checks)
        <h2 class="group">{{ __('installer::installer.requirements.groups.'.$group) }}</h2>
        <ul class="checks">
            @foreach ($checks as $check)
                <li @class(['ok' => $check->passed, 'warn' => ! $check->passed && ! $check->blocking, 'fail' => ! $check->passed && $check->blocking])>
                    <span class="icon" aria-hidden="true">{{ $check->passed ? '✓' : ($check->blocking ? '✕' : '!') }}</span>
                    <span class="check-label" dir="auto">{{ $check->label }}</span>
                    @if ($check->current !== '')
                        <span class="badge" dir="ltr">{{ $check->current }}</span>
                    @endif
                    @if (! $check->passed && $check->hint !== '')
                        <span class="hint">{{ $check->hint }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endforeach

    <div class="actions">
        @if ($report->passes())
            <a class="button" href="{{ route('installer.database', [], false) }}">{{ __('installer::installer.continue') }}</a>
        @else
            <p class="alert alert-error">{{ __('installer::installer.requirements.blocked') }}</p>
            <a class="button button-ghost" href="{{ route('installer.requirements', [], false) }}">{{ __('installer::installer.requirements.recheck') }}</a>
        @endif
    </div>
@endsection
