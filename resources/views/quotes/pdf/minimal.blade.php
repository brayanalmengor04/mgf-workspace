<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $payload['quote_number'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        .header { margin-bottom: 28px; }
        .header-table { width: 100%; border-bottom: 1px solid #222; padding-bottom: 12px; }
        .header-table td { vertical-align: top; }
        .logo { max-height: 50px; max-width: 160px; }
        .title { font-size: 18px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; }
        .meta { color: #555; margin-top: 4px; }
        .grid { width: 100%; margin-bottom: 24px; }
        .grid td { vertical-align: top; width: 50%; }
        .party-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #888; margin-bottom: 4px; }
        .party-name { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        .party-detail { color: #444; line-height: 1.5; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th { border-bottom: 1px solid #222; padding: 6px 4px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        table.items td { border-bottom: 1px solid #eee; padding: 7px 4px; }
        .text-right { text-align: right; }
        .totals { margin-top: 20px; width: 260px; margin-left: auto; }
        .totals td { padding: 3px 0; }
        .totals .total td { font-size: 14px; font-weight: bold; border-top: 1px solid #222; padding-top: 8px; }
        .footer { margin-top: 32px; font-size: 10px; color: #666; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    @if($logoDataUri)
                        <img src="{{ $logoDataUri }}" class="logo" alt="Logo"><br>
                    @endif
                    <div class="title">Cotización</div>
                </td>
                <td class="text-right">
                    <div class="meta"><strong>{{ $payload['quote_number'] }}</strong></div>
                    <div class="meta">{{ \Carbon\Carbon::parse($payload['issued_at'])->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="grid">
        <tr>
            <td>
                <div class="party-label">De (Emisor)</div>
                <div class="party-name">{{ $payload['issuer']['name'] }}</div>
                <div class="party-detail">
                    @if($payload['issuer']['ruc'])
                        RUC: {{ $payload['issuer']['ruc'] }}@if($payload['issuer']['has_dv'] && $payload['issuer']['dv']) DV {{ $payload['issuer']['dv'] }}@endif<br>
                    @endif
                    @if($payload['issuer']['address']){{ $payload['issuer']['address'] }}<br>@endif
                    @if($payload['issuer']['phone'])Tel: {{ $payload['issuer']['phone'] }}<br>@endif
                    @if($payload['issuer']['email']){{ $payload['issuer']['email'] }}@endif
                </div>
            </td>
            <td>
                <div class="party-label">Para (Destinatario)</div>
                <div class="party-name">{{ $payload['recipient']['name'] }}</div>
                <div class="party-detail">
                    @if($payload['recipient']['ruc'])
                        RUC: {{ $payload['recipient']['ruc'] }}@if($payload['recipient']['has_dv'] && $payload['recipient']['dv']) DV {{ $payload['recipient']['dv'] }}@endif<br>
                    @endif
                    @if($payload['recipient']['address']){{ $payload['recipient']['address'] }}<br>@endif
                    @if($payload['recipient']['phone'])Tel: {{ $payload['recipient']['phone'] }}<br>@endif
                    @if($payload['recipient']['email']){{ $payload['recipient']['email'] }}@endif
                </div>
            </td>
        </tr>
    </table>

    @include('quotes.pdf.partials.items-totals')
    @include('quotes.pdf.partials.footer')
</body>
</html>
