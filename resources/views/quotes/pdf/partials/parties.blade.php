<table class="grid">
    <tr>
        <td>
            <div class="box">
                <h3>De (Emisor)</h3>
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
            <div class="box">
                <h3>Para (Destinatario)</h3>
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
