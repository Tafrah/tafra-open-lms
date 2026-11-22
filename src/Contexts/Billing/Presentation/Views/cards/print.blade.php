<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('billing::billing.card.print.title', ['name' => $group->name]) }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Almarai', system-ui, sans-serif; margin: 0; color: #071437; background: #fff; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4mm; }
        .card { border: 1px dashed #99A1B7; border-radius: 8px; padding: 4mm; break-inside: avoid; min-height: 30mm; display: flex; flex-direction: column; justify-content: space-between; }
        .head { display: flex; align-items: center; justify-content: space-between; gap: 2mm; }
        .brand { display: flex; align-items: center; gap: 2mm; min-width: 0; }
        .logo { height: 8mm; width: auto; max-width: 25mm; object-fit: contain; }
        .platform { font-weight: 800; font-size: 9pt; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .serial { font-family: ui-monospace, monospace; font-size: 7pt; color: #78829D; direction: ltr; white-space: nowrap; }
        .name { font-weight: 800; font-size: 11pt; }
        .value { color: #419DF1; font-weight: 700; font-size: 10pt; }
        .code { font-family: ui-monospace, monospace; font-size: 14pt; letter-spacing: 1px; direction: ltr; text-align: center; padding: 2mm 0; font-weight: 700; }
        .how { font-size: 7pt; color: #78829D; }
        .toolbar { padding: 12px; text-align: center; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">🖨 {{ __('billing::billing.card_group.actions.print') }}</button></div>
    <div class="grid">
        @foreach ($cards as $card)
            <div class="card">
                <div>
                    <div class="head">
                        <div class="brand">
                            @if ($logoUrl)
                                <img class="logo" src="{{ $logoUrl }}" alt="{{ $platformName }}">
                            @else
                                <span class="platform">{{ $platformName }}</span>
                            @endif
                        </div>
                        <span class="serial" title="{{ __('billing::billing.card.print.serial') }}">{{ __('billing::billing.card.print.serial_short') }} {{ $card['serial'] }}</span>
                    </div>
                    <div class="name">{{ $group->name }}</div>
                    <div class="value">{{ $group->valueLabel() }}</div>
                </div>
                <div class="code">{{ implode(' ', str_split($card['code'], 4)) }}</div>
                <div class="how">{{ __('billing::billing.card.print.how', ['url' => preg_replace('#^https?://#', '', $url)]) }}</div>
            </div>
        @endforeach
    </div>
</body>
</html>
