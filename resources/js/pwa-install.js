let deferredInstallPrompt = null;

function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
}

function hideInstallButtons() {
    document.querySelectorAll('[data-pwa-install-root]').forEach((root) => {
        root.classList.add('hidden');
    });
}

function showInstallButtons() {
    document.querySelectorAll('[data-pwa-install-root]').forEach((root) => {
        root.classList.remove('hidden');
    });
}

function initPwaInstallRoot(root) {
    const button = root.querySelector('[data-pwa-install]');

    if (! button) {
        return;
    }

    button.addEventListener('click', async () => {
        if (deferredInstallPrompt) {
            deferredInstallPrompt.prompt();
            const { outcome } = await deferredInstallPrompt.userChoice;
            deferredInstallPrompt = null;

            if (outcome === 'accepted') {
                hideInstallButtons();
            }

            return;
        }

        const onAdminLogin = window.location.pathname.endsWith('/admin/login');

        if (onAdminLogin) {
            button.textContent = 'Usa el icono Instalar en la barra del navegador';

            return;
        }

        const panelUrl = button.dataset.pwaPanelUrl;

        if (panelUrl) {
            window.location.href = panelUrl;
        }
    });
}

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    showInstallButtons();
});

window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    hideInstallButtons();
});

document.addEventListener('DOMContentLoaded', () => {
    if (isStandalone()) {
        hideInstallButtons();

        return;
    }

    document.querySelectorAll('[data-pwa-install-root]').forEach(initPwaInstallRoot);

    if (new URLSearchParams(window.location.search).get('install') === '1' && deferredInstallPrompt) {
        showInstallButtons();
    }
});
