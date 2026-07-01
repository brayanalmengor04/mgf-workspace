@if (config('pwa-service-worker.enabled'))
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('{{ url(config('pwa-service-worker.path', 'sw.js')) }}');
    }
</script>
@endif
