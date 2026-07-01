@if (config('pwa-service-worker.enabled'))
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('{{ url(config('pwa-service-worker.path', 'sw.js')) }}');

        let reloading = false;

        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data?.type !== 'pwa-updated' || ! navigator.serviceWorker.controller) {
                return;
            }

            if (window.confirm('Hay una nueva versión disponible. ¿Recargar ahora?')) {
                window.location.reload();
            }
        });

        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (reloading) {
                return;
            }

            reloading = true;
        });
    }

    (function () {
        const bannerId = 'pwa-offline-banner';
        let onlineTimeout;

        function ensureBanner() {
            let banner = document.getElementById(bannerId);

            if (! banner) {
                banner = document.createElement('div');
                banner.id = bannerId;
                banner.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:99999;padding:0.625rem 1rem;text-align:center;font-size:0.875rem;font-weight:600;transition:transform 0.2s ease,opacity 0.2s ease;';
                document.body.appendChild(banner);
            }

            return banner;
        }

        function setBanner(message, background) {
            const banner = ensureBanner();
            banner.textContent = message;
            banner.style.background = background;
            banner.style.color = '#fff';
            banner.style.transform = 'translateY(0)';
            banner.style.opacity = '1';
        }

        function hideBanner() {
            const banner = document.getElementById(bannerId);

            if (banner) {
                banner.style.transform = 'translateY(-100%)';
                banner.style.opacity = '0';
            }
        }

        window.addEventListener('offline', () => {
            clearTimeout(onlineTimeout);
            setBanner('Sin conexión. El panel requiere internet para funcionar.', '#b45309');
        });

        window.addEventListener('online', () => {
            setBanner('Conexión restaurada.', '#15803d');
            onlineTimeout = setTimeout(hideBanner, 2500);
        });

        if (! navigator.onLine) {
            setBanner('Sin conexión. El panel requiere internet para funcionar.', '#b45309');
        }
    })();
</script>
@endif
