<div class="footer">
    @if($payload['footer']['bank_name'] || $payload['footer']['bank_account_number'])
        <div><strong>Cuenta bancaria:</strong> {{ $payload['footer']['bank_name'] }} — {{ $payload['footer']['bank_account_number'] }}</div>
    @endif
    @if($payload['footer']['yappy_id'])
        <div><strong>Yappy:</strong> {{ $payload['footer']['yappy_id'] }}</div>
    @endif
    @if($payload['footer']['notes'])
        <div style="margin-top: 8px;">{{ $payload['footer']['notes'] }}</div>
    @endif
</div>
