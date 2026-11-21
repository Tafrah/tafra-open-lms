<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar', 'he', 'fa', 'ur'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('tenancy::tenancy.tenant.suspended_page.title') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: system-ui, sans-serif; line-height: 1.6; }
        main { max-width: 32rem; padding-inline: 1.5rem; text-align: start; }
        h1 { font-size: 1.5rem; margin-block: 0 .75rem; }
        p { margin-block: 0 1rem; }
        dt { font-weight: 600; }
        dd { margin-inline-start: 0; margin-block-end: 1rem; }
    </style>
</head>
<body>
    <main>
        <h1>{{ __('tenancy::tenancy.tenant.suspended_page.title') }}</h1>

        <p>{{ __('tenancy::tenancy.tenant.suspended_page.body', ['name' => $tenant->name]) }}</p>

        @if (filled($tenant->suspension_reason))
            <dl>
                <dt>{{ __('tenancy::tenancy.tenant.suspended_page.reason') }}</dt>
                <dd>{{ $tenant->suspension_reason }}</dd>
            </dl>
        @endif

        <p><a href="{{ url('/') }}">{{ __('tenancy::tenancy.tenant.suspended_page.back') }}</a></p>
    </main>
</body>
</html>
