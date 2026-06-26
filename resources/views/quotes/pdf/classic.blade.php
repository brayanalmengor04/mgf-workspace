<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $payload['quote_number'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        .header { margin-bottom: 24px; border-bottom: 2px solid {{ $primaryColor }}; padding-bottom: 12px; }
        .header-top { width: 100%; margin-bottom: 8px; }
        .header-top td { vertical-align: middle; }
        .logo { max-height: 60px; max-width: 180px; }
        .title { font-size: 22px; font-weight: bold; color: {{ $primaryColor }}; }
        .muted { color: #666; }
        .grid { width: 100%; margin-bottom: 20px; }
        .grid td { vertical-align: top; width: 50%; }
        .box { border: 1px solid #ddd; padding: 10px; border-radius: 4px; }
        .box h3 { margin: 0 0 8px; font-size: 13px; text-transform: uppercase; color: #555; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th, table.items td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table.items th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .totals { margin-top: 16px; width: 280px; margin-left: auto; }
        .totals td { padding: 4px 0; }
        .totals .total { font-size: 16px; font-weight: bold; border-top: 2px solid #333; padding-top: 8px; }
        .footer { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 12px; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        @if($logoDataUri)
            <table class="header-top">
                <tr>
                    <td><img src="{{ $logoDataUri }}" class="logo" alt="Logo"></td>
                    <td class="text-right">
                        <div class="title">COTIZACIÓN</div>
                        <div><strong>{{ $payload['quote_number'] }}</strong></div>
                        <div class="muted">Fecha: {{ \Carbon\Carbon::parse($payload['issued_at'])->format('d/m/Y') }}</div>
                    </td>
                </tr>
            </table>
        @else
            <div class="title">COTIZACIÓN</div>
            <div><strong>{{ $payload['quote_number'] }}</strong></div>
            <div class="muted">Fecha: {{ \Carbon\Carbon::parse($payload['issued_at'])->format('d/m/Y') }}</div>
        @endif
    </div>

    @include('quotes.pdf.partials.parties')
    @include('quotes.pdf.partials.items-totals')
    @include('quotes.pdf.partials.footer')
</body>
</html>
