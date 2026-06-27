<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $payload['quote_number'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #2c2c2c; }
        .frame { border: 2px solid {{ $primaryColor }}; padding: 20px 22px; }
        .inner-frame { border: 1px solid #d4d4d4; padding: 18px 20px; }
        .header { text-align: center; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px double {{ $primaryColor }}; }
        .logo { max-height: 52px; max-width: 150px; margin-bottom: 8px; }
        .title { font-size: 24px; font-weight: normal; letter-spacing: 4px; text-transform: uppercase; color: {{ $primaryColor }}; margin: 0; }
        .subtitle { margin-top: 6px; color: #666; font-size: 11px; }
        .grid { width: 100%; margin-bottom: 18px; }
        .grid td { vertical-align: top; width: 50%; padding: 0 10px; }
        .party-box { border-top: 1px solid #e0e0e0; border-bottom: 1px solid #e0e0e0; padding: 10px 0; }
        .party-label { font-size: 9px; text-transform: uppercase; letter-spacing: 2px; color: {{ $primaryColor }}; margin-bottom: 6px; }
        .party-name { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th { border-top: 1px solid #333; border-bottom: 1px solid #333; padding: 7px 6px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        table.items td { border-bottom: 1px solid #eee; padding: 8px 6px; }
        .text-right { text-align: right; }
        .totals { margin-top: 16px; width: 270px; margin-left: auto; }
        .totals td { padding: 4px 0; }
        .totals .total td { font-size: 14px; font-weight: bold; border-top: 1px double #333; padding-top: 8px; }
        .footer { margin-top: 22px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #eee; padding-top: 12px; }
    </style>
</head>
<body>
    <div class="frame">
        <div class="inner-frame">
            <div class="header">
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" class="logo" alt="Logo"><br>
                @endif
                <h1 class="title">Cotización</h1>
                <div class="subtitle">
                    <strong>{{ $payload['quote_number'] }}</strong>
                    &nbsp;·&nbsp;
                    {{ \Carbon\Carbon::parse($payload['issued_at'])->format('d/m/Y') }}
                </div>
            </div>

            <table class="grid">
                <tr>
                    <td>
                        <div class="party-box">
                            <div class="party-label">Emisor</div>
                            <div class="party-name">{{ $payload['issuer']['name'] }}</div>
                            @if($payload['issuer']['ruc'])
                                <div>RUC: {{ $payload['issuer']['ruc'] }}@if($payload['issuer']['has_dv'] && $payload['issuer']['dv']) DV {{ $payload['issuer']['dv'] }}@endif</div>
                            @endif
                            @if($payload['issuer']['address'])<div>{{ $payload['issuer']['address'] }}</div>@endif
                            @if($payload['issuer']['phone'])<div>{{ $payload['issuer']['phone'] }}</div>@endif
                            @if($payload['issuer']['email'])<div>{{ $payload['issuer']['email'] }}</div>@endif
                        </div>
                    </td>
                    <td>
                        <div class="party-box">
                            <div class="party-label">Destinatario</div>
                            <div class="party-name">{{ $payload['recipient']['name'] }}</div>
                            @if($payload['recipient']['ruc'])
                                <div>RUC: {{ $payload['recipient']['ruc'] }}@if($payload['recipient']['has_dv'] && $payload['recipient']['dv']) DV {{ $payload['recipient']['dv'] }}@endif</div>
                            @endif
                            @if($payload['recipient']['address'])<div>{{ $payload['recipient']['address'] }}</div>@endif
                            @if($payload['recipient']['phone'])<div>{{ $payload['recipient']['phone'] }}</div>@endif
                            @if($payload['recipient']['email'])<div>{{ $payload['recipient']['email'] }}</div>@endif
                        </div>
                    </td>
                </tr>
            </table>

            @include('quotes.pdf.partials.items-totals')
            @include('quotes.pdf.partials.footer')
        </div>
    </div>
</body>
</html>
