<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $payload['quote_number'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; line-height: 1.35; }
        .header { margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid {{ $primaryColor }}; }
        .header-table { width: 100%; }
        .header-table td { vertical-align: top; }
        .logo { max-height: 42px; max-width: 130px; }
        .title { font-size: 16px; font-weight: bold; color: {{ $primaryColor }}; margin: 0; }
        .meta { font-size: 9px; color: #555; }
        .parties { width: 100%; margin-bottom: 10px; font-size: 9px; }
        .parties td { vertical-align: top; width: 50%; padding: 0 8px 0 0; }
        .party-label { font-weight: bold; color: {{ $primaryColor }}; font-size: 8px; text-transform: uppercase; margin-bottom: 2px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 9px; }
        table.items th, table.items td { border: 1px solid #ccc; padding: 4px 5px; text-align: left; }
        table.items th { background: {{ $primaryColor }}; color: #fff; font-size: 8px; padding: 5px; }
        .text-right { text-align: right; }
        .totals { margin-top: 8px; width: 220px; margin-left: auto; font-size: 9px; }
        .totals td { padding: 2px 0; }
        .totals .total td { font-size: 11px; font-weight: bold; border-top: 1px solid #333; padding-top: 4px; }
        .footer { margin-top: 12px; font-size: 8px; color: #555; border-top: 1px dashed #ccc; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 60%">
                    @if($logoDataUri)
                        <img src="{{ $logoDataUri }}" class="logo" alt="Logo"><br>
                    @endif
                    <div class="title">COTIZACIÓN</div>
                </td>
                <td class="text-right">
                    <div class="meta"><strong>{{ $payload['quote_number'] }}</strong></div>
                    <div class="meta">{{ \Carbon\Carbon::parse($payload['issued_at'])->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="parties">
        <tr>
            <td>
                <div class="party-label">De</div>
                <strong>{{ $payload['issuer']['name'] }}</strong><br>
                @if($payload['issuer']['ruc'])RUC: {{ $payload['issuer']['ruc'] }}@if($payload['issuer']['has_dv'] && $payload['issuer']['dv']) DV {{ $payload['issuer']['dv'] }}@endif<br>@endif
                @if($payload['issuer']['phone']){{ $payload['issuer']['phone'] }} · @endif
                @if($payload['issuer']['email']){{ $payload['issuer']['email'] }}@endif
            </td>
            <td>
                <div class="party-label">Para</div>
                <strong>{{ $payload['recipient']['name'] }}</strong><br>
                @if($payload['recipient']['ruc'])RUC: {{ $payload['recipient']['ruc'] }}@if($payload['recipient']['has_dv'] && $payload['recipient']['dv']) DV {{ $payload['recipient']['dv'] }}@endif<br>@endif
                @if($payload['recipient']['phone']){{ $payload['recipient']['phone'] }} · @endif
                @if($payload['recipient']['email']){{ $payload['recipient']['email'] }}@endif
            </td>
        </tr>
    </table>

    @include('quotes.pdf.partials.items-totals')
    @include('quotes.pdf.partials.footer')
</body>
</html>
