@extends('installer::layout')

@section('title', __('installer::installer.finished.title'))
@section('lead', __('installer::installer.finished.lead', ['email' => $email]))

@section('content')
    <div class="actions actions-start">
        <a class="button" href="{{ $adminUrl }}">{{ __('installer::installer.finished.open_admin') }}</a>
        <a class="button button-ghost" href="{{ $siteUrl }}">{{ __('installer::installer.finished.open_site') }}</a>
    </div>

    <h2 class="group">{{ __('installer::installer.finished.cron') }}</h2>
    <p>{{ __('installer::installer.finished.cron_help') }}</p>
    <pre class="code" dir="ltr">{{ $cron }}</pre>
    <p class="hint">{{ __('installer::installer.finished.cron_php_hint') }}</p>

    <h2 class="group">{{ __('installer::installer.finished.media') }}</h2>
    <p>{{ __('installer::installer.finished.media_modes.'.$result->mediaServing->value) }}</p>

    <h2 class="group">{{ __('installer::installer.finished.next') }}</h2>
    <ul class="list">
        <li>{{ __('installer::installer.finished.next_mail') }}</li>
        <li>{{ __('installer::installer.finished.next_ssl') }}</li>
        <li>{{ __('installer::installer.finished.next_backup') }}</li>
    </ul>

    <p class="muted">{{ __('installer::installer.finished.version', ['version' => $result->version]) }}</p>
@endsection
