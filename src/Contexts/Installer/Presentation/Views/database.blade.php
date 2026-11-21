@extends('installer::layout')

@section('title', __('installer::installer.database.title'))
@section('lead', __('installer::installer.database.lead'))

@section('content')
    <div class="alert alert-info">{{ __('installer::installer.database.cpanel_hint') }}</div>

    <form method="post" action="{{ route('installer.database.save', [], false) }}" class="form" onsubmit="this.querySelector('button[type=submit]').disabled = true">
        @csrf

        <label class="field">
            <span>{{ __('installer::installer.database.fields.db_driver') }}</span>
            <select name="db_driver">
                @foreach ($drivers as $driver)
                    <option value="{{ $driver }}" @selected(old('db_driver', $defaults['db_driver']) === $driver)>{{ __('installer::installer.database.drivers.'.$driver) }}</option>
                @endforeach
            </select>
        </label>

        <div class="row">
            <label class="field grow">
                <span>{{ __('installer::installer.database.fields.db_host') }}</span>
                <input type="text" name="db_host" dir="ltr" required value="{{ old('db_host', $defaults['db_host']) }}">
            </label>
            <label class="field narrow">
                <span>{{ __('installer::installer.database.fields.db_port') }}</span>
                <input type="number" name="db_port" dir="ltr" required min="1" max="65535" value="{{ old('db_port', $defaults['db_port']) }}">
            </label>
        </div>

        <label class="field">
            <span>{{ __('installer::installer.database.fields.db_database') }}</span>
            <input type="text" name="db_database" dir="ltr" required autocomplete="off" value="{{ old('db_database', $defaults['db_database']) }}">
        </label>

        <label class="field">
            <span>{{ __('installer::installer.database.fields.db_username') }}</span>
            <input type="text" name="db_username" dir="ltr" required autocomplete="off" value="{{ old('db_username', $defaults['db_username']) }}">
        </label>

        <label class="field">
            <span>{{ __('installer::installer.database.fields.db_password') }}</span>
            <input type="password" name="db_password" dir="ltr" autocomplete="new-password">
        </label>

        <div class="actions">
            <a class="button button-ghost" href="{{ route('installer.requirements', [], false) }}">{{ __('installer::installer.back') }}</a>
            <button type="submit" class="button">{{ __('installer::installer.database.submit') }}</button>
        </div>
    </form>
@endsection
