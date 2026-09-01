@props([
    'id',
    'height' => '16rem',
])

<div
    {{ $attributes->class(['mgf-crm-chart-wrap']) }}
    wire:ignore
    style="min-height: {{ $height }};"
>
    <div id="{{ $id }}" style="width:100%;height:{{ $height }};"></div>
</div>
