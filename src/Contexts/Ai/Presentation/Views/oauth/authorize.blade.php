@php
    $rtl = in_array(app()->getLocale(), ['ar', 'he', 'fa', 'ur'], true);
    $redirectHosts = collect((array) ($client->redirect_uris ?? []))
        ->map(fn ($uri) => parse_url((string) $uri, PHP_URL_HOST))
        ->filter()
        ->unique()
        ->implode('، ');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="referrer" content="no-referrer">
    <title>{{ __('ai::ai.consent.title', ['client' => $client->name]) }}</title>
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: system-ui, sans-serif; line-height: 1.6; padding-block: 2rem; }
        main { width: min(34rem, 100%); padding-inline: 1.5rem; text-align: start; }
        h1 { font-size: 1.4rem; margin-block: 0 .5rem; }
        p { margin-block: 0 1rem; }
        .muted { opacity: .75; font-size: .9rem; }
        fieldset { border: 1px solid color-mix(in srgb, currentColor 20%, transparent); border-radius: .75rem; padding: 1rem; margin-block: 0 1rem; }
        legend { font-weight: 600; padding-inline: .25rem; }
        label { display: flex; gap: .6rem; align-items: flex-start; padding-block: .35rem; cursor: pointer; }
        label small { display: block; opacity: .75; }
        ul { margin-block: 0 1rem; padding-inline-start: 1.25rem; }
        .actions { display: flex; gap: .75rem; flex-wrap: wrap; }
        button { font: inherit; padding: .6rem 1.2rem; border-radius: .6rem; border: 1px solid transparent; cursor: pointer; }
        /* ألوان النظام بدل hex — الصفحة برا ثيم Filament */
        .approve { background: AccentColor; color: AccentColorText; }
        .deny { background: transparent; border-color: color-mix(in srgb, currentColor 30%, transparent); color: inherit; }
        form.inline { display: inline; }
    </style>
</head>
<body>
    <main>
        <h1>{{ __('ai::ai.consent.title', ['client' => $client->name]) }}</h1>

        <p>{{ __('ai::ai.consent.intro', ['client' => $client->name, 'app' => config('app.name')]) }}</p>

        <ul>
            <li>{{ __('ai::ai.consent.points.as_you') }}</li>
            <li>{{ __('ai::ai.consent.points.never_more') }}</li>
            <li>{{ __('ai::ai.consent.points.logged') }}</li>
            <li>{{ __('ai::ai.consent.points.revoke') }}</li>
        </ul>

        <p class="muted">
            {{ __('ai::ai.consent.signed_in_as', ['email' => $user->email]) }}
            @if ($redirectHosts !== '')
                <br>{{ __('ai::ai.consent.redirects_to', ['hosts' => $redirectHosts]) }}
            @endif
        </p>

        @if ($denied)
            <p>{{ __('ai::ai.consent.no_targets') }}</p>
        @else
            <form method="post" action="{{ route('ai.oauth.consent') }}" id="approve">
                @csrf
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">

                <fieldset>
                    <legend>{{ __('ai::ai.consent.organization') }}</legend>
                    @foreach ($targets as $target)
                        <label>
                            <input type="radio" name="tenant" value="{{ $target['value'] }}" @checked($loop->first) required>
                            <span>
                                {{ $target['label'] }}
                                @unless ($target['writes'])
                                    <small>{{ __('ai::ai.consent.read_only_org') }}</small>
                                @endunless
                            </span>
                        </label>
                    @endforeach
                </fieldset>

                <fieldset>
                    <legend>{{ __('ai::ai.consent.access') }}</legend>
                    @foreach ($levels as $level)
                        <label>
                            <input type="radio" name="access" value="{{ $level->value }}" @checked($loop->first) required>
                            <span>
                                {{ $level->getLabel() }}
                                <small>{{ $level->getDescription() }}</small>
                            </span>
                        </label>
                    @endforeach
                </fieldset>
            </form>
        @endif

        <div class="actions">
            @unless ($denied)
                <button type="submit" form="approve" class="approve">{{ __('ai::ai.consent.approve') }}</button>
            @endunless

            <form method="post" action="{{ route('passport.authorizations.deny') }}" class="inline">
                @csrf
                @method('DELETE')
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="deny">{{ __('ai::ai.consent.deny') }}</button>
            </form>
        </div>
    </main>
</body>
</html>
