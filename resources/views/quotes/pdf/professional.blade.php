<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $payload['quote_number'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; margin: 0; }
        .layout { width: 100%; border-collapse: collapse; }
        .sidebar { width: 8px; background: {{ $primaryColor }}; }
        .content { padding: 0 0 0 16px; vertical-align: top; }
        .header { border-bottom: 1px solid #e5e7eb; padding-bottom: 14px; margin-bottom: 18px; }
        .header-table { width: 100%; }
        .header-table td { vertical-align: middle; }
        .logo { max-height: 58px; max-width: 170px; }
        .title { font-size: 20px; font-weight: bold; color: {{ $primaryColor }}; text-transform: uppercase; letter-spacing: 0.5px; }
        .meta { color: #6b7280; margin-top: 4px; }
        .section-title { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: {{ $primaryColor }}; margin: 0 0 8px; font-weight: bold; }
        .grid { width: 100%; margin-bottom: 18px; }
        .grid td { vertical-align: top; width: 50%; padding-right: 12px; }
        .box { background: #f9fafb; border: 1px solid #e5e7eb; padding: 12px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th, table.items td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        table.items th { background: #f3f4f6; color: #374151; font-size: 10px; text-transform: uppercase; }
        .text-right { text-align: right; }
        .totals { margin-top: 14px; width: 290px; margin-left: auto; background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 12px; }
        .totals td { padding: 4px 0; }
        .totals .total td { font-size: 15px; font-weight: bold; border-top: 2px solid {{ $primaryColor }}; padding-top: 8px; color: {{ $primaryColor }}; }
        .footer { margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 12px; font-size: 11px; color: #4b5563; }
    </style>
</head>
<body>
    <table class="layout">
        <tr>
            <td class="sidebar">&nbsp;</td>
            <td class="content">
                <div class="header">
                    <table class="header-table">
                        <tr>
                            <td>
                                @if($logoDataUri)
                                    <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
                                @else
                                    <div class="title">Cotización</div>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="title">Cotización</div>
                                <div class="meta"><strong>{{ $payload['quote_number'] }}</strong></div>
                                <div class="meta">Fecha: {{ \Carbon\Carbon::parse($payload['issued_at'])->format('d/m/Y') }}</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <table class="grid">
                    <tr>
                        <td>
                            <div class="section-title">Emisor</div>
                            <div class="box">
                                <div><strong>{{ $payload['issuer']['name'] }}</strong></div>
                                @if($payload['issuer']['ruc'])
                                    <div>RUC: {{ $payload['issuer']['ruc'] }}@if($payload['issuer']['has_dv'] && $payload['issuer']['dv']) DV {{ $payload['issuer']['dv'] }}@endif</div>
                                @endif
                                @if($payload['issuer']['address'])<div>{{ $payload['issuer']['address'] }}</div>@endif
                                @if($payload['issuer']['phone'])<div>Tel: {{ $payload['issuer']['phone'] }}</div>@endif
                                @if($payload['issuer']['email'])<div>{{ $payload['issuer']['email'] }}</div>@endif
                            </div>
                        </td>
                        <td>
                            <div class="section-title">Cliente</div>
                            <div class="box">
                                <div><strong>{{ $payload['recipient']['name'] }}</strong></div>
                                @if($payload['recipient']['ruc'])
                                    <div>RUC: {{ $payload['recipient']['ruc'] }}@if($payload['recipient']['has_dv'] && $payload['recipient']['dv']) DV {{ $payload['recipient']['dv'] }}@endif</div>
                                @endif
                                @if($payload['recipient']['address'])<div>{{ $payload['recipient']['address'] }}</div>@endif
                                @if($payload['recipient']['phone'])<div>Tel: {{ $payload['recipient']['phone'] }}</div>@endif
                                @if($payload['recipient']['email'])<div>{{ $payload['recipient']['email'] }}</div>@endif
                            </div>
                        </td>
                    </tr>
                </table>

                @include('quotes.pdf.partials.items-totals')
                @include('quotes.pdf.partials.footer')
            </td>
        </tr>
    </table>
</body>
</html>
