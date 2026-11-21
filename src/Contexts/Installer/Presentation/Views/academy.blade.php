@extends('installer::layout')

@section('title', __('installer::installer.academy.title'))
@section('lead', __('installer::installer.academy.lead'))

@section('content')
    <form method="post" action="{{ route('installer.install', [], false) }}" class="form" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.classList.add('busy')">
        @csrf

        <h2 class="group">{{ __('installer::installer.academy.platform') }}</h2>

        <label class="field">
            <span>{{ __('installer::installer.academy.fields.platform_name') }}</span>
            <input type="text" name="platform_name" required maxlength="120" value="{{ old('platform_name') }}" placeholder="{{ __('installer::installer.academy.placeholders.platform_name') }}">
        </label>

        <div class="row">
            <label class="field grow">
                <span>{{ __('installer::installer.academy.fields.teacher_name') }}</span>
                <input type="text" name="teacher_name" required maxlength="120" value="{{ old('teacher_name') }}" placeholder="{{ __('installer::installer.academy.placeholders.teacher_name') }}">
            </label>
            <label class="field grow">
                <span>{{ __('installer::installer.academy.fields.subject') }}</span>
                <input type="text" name="subject" required maxlength="120" value="{{ old('subject') }}" placeholder="{{ __('installer::installer.academy.placeholders.subject') }}">
            </label>
        </div>

        <h2 class="group">{{ __('installer::installer.academy.account') }}</h2>

        <div class="row">
            <label class="field grow">
                <span>{{ __('installer::installer.academy.fields.admin_email') }}</span>
                <input type="email" name="admin_email" dir="ltr" required autocomplete="email" value="{{ old('admin_email') }}">
            </label>
            <label class="field grow">
                <span>{{ __('installer::installer.academy.fields.admin_phone') }}</span>
                <input type="tel" name="admin_phone" dir="ltr" autocomplete="tel" value="{{ old('admin_phone') }}" placeholder="+20 10 0000 0000">
            </label>
        </div>

        <div class="row">
            <label class="field grow">
                <span>{{ __('installer::installer.academy.fields.admin_password') }}</span>
                <input type="password" name="admin_password" dir="ltr" required minlength="8" autocomplete="new-password">
            </label>
            <label class="field grow">
                <span>{{ __('installer::installer.academy.fields.admin_password_confirmation') }}</span>
                <input type="password" name="admin_password_confirmation" dir="ltr" required minlength="8" autocomplete="new-password">
            </label>
        </div>
        <p class="hint">{{ __('installer::installer.academy.password_hint') }}</p>

        <div class="actions">
            <a class="button button-ghost" href="{{ route('installer.database', [], false) }}">{{ __('installer::installer.back') }}</a>
            <button type="submit" class="button">{{ __('installer::installer.academy.submit') }}</button>
        </div>
        <p class="busy-note muted">{{ __('installer::installer.academy.wait') }}</p>
    </form>
@endsection
