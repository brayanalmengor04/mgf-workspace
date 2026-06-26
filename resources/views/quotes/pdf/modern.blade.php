<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $payload['quote_number'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; margin: 0; padding: 0; }
        .banner { background: {{ $primaryColor }}; color: #fff; padding: 20px 24px; margin-bottom: 24px; }
        .banner-table { width: 100%; }
        .banner-table td { vertical-align: middle; color: #fff; }
        .logo { max-height: 70px; max-width: 200px; }
        .banner-title { font-size: 26px; font-weight: bold; margin: 0; }
        .banner-meta { font-size: 12px; opacity: 0.9; margin-top: 4px; }
        .content { padding: 0 24px 24px; }
        .grid { width: 100%; margin-bottom: 20px; }
        .grid td { vertical-align: top; width: 50%; padding-right: 10px; }
        .box { border-left: 3px solid {{ $primaryColor }}; padding: 10px 12px; background: #fafafa; }
        .box h3 { margin: 0 0 8px; font-size: 11px; text-transform: uppercase; color: {{ $primaryColor }}; letter-spacing: 0.5px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th, table.items td { border: 1px solid #e5e5e5; padding: 8px; text-align: left; }
        table.items th { background: {{ $primaryColor }}; color: #fff; font-weight: bold; }
        table.items tbody tr:nth-child(even) { background: #f9f9f9; }
        .text-right { text-align: right; }
        .totals { margin-top: 16px; width: 300px; margin-left: auto; }
        .totals td { padding: 5px 0; }
        .totals .total td { font-size: 16px; font-weight: bold; border-top: 2px solid {{ $primaryColor }}; padding-top: 8px; color: {{ $primaryColor }}; }
        .footer { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 12px; font-size: 11px; color: #555; }
    </style>
</head>
<body>
    <div class="banner">
        <table class="banner-table">
            <tr>
                <td style="width: 55%">
                    @if($logoDataUri)
                        <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
                    @else
                        <div class="banner-title">COTIZACIÓN</div>
                    @endif
                </td>
                <td class="text-right">
                    @if($logoDataUri)
                        <div class="banner-title">COTIZACIÓN</div>
                    @endif
                    <div class="banner-meta"><strong>{{ $payload['quote_number'] }}</strong></div>
                    <div class="banner-meta">Fecha: {{ \Carbon\Carbon::parse($payload['issued_at'])->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        @include('quotes.pdf.partials.parties')
        @include('quotes.pdf.partials.items-totals')
        @include('quotes.pdf.partials.footer')
    </div>
</body>
</html>
