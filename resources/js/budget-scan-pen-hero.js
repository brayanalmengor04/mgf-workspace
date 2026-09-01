const MANIFEST_URL = '/animations/budget-scan-pen/manifest.json';

function padFrame(index) {
    return String(index).padStart(4, '0');
}

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function isMobileViewport() {
    return window.matchMedia('(max-width: 900px)').matches;
}

class BudgetScanPenHero {
    constructor(root) {
        this.root = root;
        this.canvas = root.querySelector('[data-pen-canvas]');
        this.gif = root.querySelector('[data-pen-gif]');
        this.paper = root.querySelector('[data-pen-paper]');
        this.ctx = this.canvas?.getContext('2d', { alpha: true }) ?? null;

        this.manifest = null;
        this.images = [];
        this.frameIndex = 0;
        this.lastTick = 0;
        this.fps = 20;
        this.speed = 1;
        this.rafId = null;
        this.loaded = false;
        this.parallaxX = 0;
        this.parallaxY = 0;

        this.onFrame = this.onFrame.bind(this);
        this.onParallax = this.onParallax.bind(this);
        this.onVisibility = this.onVisibility.bind(this);
    }

    async init() {
        if (!this.canvas || !this.ctx) {
            this.showGif();
            return;
        }

        this.showGif();

        if (prefersReducedMotion() || isMobileViewport()) {
            return;
        }

        try {
            const response = await fetch(MANIFEST_URL);
            if (!response.ok) {
                throw new Error('manifest missing');
            }

            this.manifest = await response.json();
            this.fps = this.manifest.fps ?? 20;

            await this.preloadFrames();
            this.resizeCanvas();
            this.loaded = true;
            this.canvas.classList.add('is-ready');
            this.gif?.classList.remove('is-visible');

            window.addEventListener('resize', () => this.resizeCanvas());
            document.addEventListener('visibilitychange', this.onVisibility);
            this.root.addEventListener('pointermove', this.onParallax);

            this.lastTick = performance.now();
            this.rafId = requestAnimationFrame(this.onFrame);
        } catch {
            this.showGif();
        }
    }

    async preloadFrames() {
        const { frameCount, framesBase, framesExt } = this.manifest;
        const batchSize = 12;

        for (let start = 0; start < frameCount; start += batchSize) {
            const end = Math.min(start + batchSize, frameCount);
            const batch = [];

            for (let index = start; index < end; index += 1) {
                batch.push(
                    new Promise((resolve, reject) => {
                        const image = new Image();
                        image.decoding = 'async';
                        image.onload = () => resolve(image);
                        image.onerror = reject;
                        image.src = `${framesBase}${padFrame(index)}${framesExt}`;
                    }),
                );
            }

            const loaded = await Promise.all(batch);
            this.images.push(...loaded);
        }
    }

    resizeCanvas() {
        if (!this.images.length) {
            return;
        }

        const sample = this.images[0];
        const displayWidth = this.canvas.clientWidth || sample.width;
        const ratio = sample.height / sample.width;
        const displayHeight = displayWidth * ratio;
        const dpr = Math.min(window.devicePixelRatio || 1, 2);

        this.canvas.width = Math.round(displayWidth * dpr);
        this.canvas.height = Math.round(displayHeight * dpr);
        this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    onFrame(timestamp) {
        if (!this.loaded) {
            return;
        }

        const interval = 1000 / (this.fps * this.speed);
        const elapsed = timestamp - this.lastTick;

        if (elapsed >= interval) {
            this.frameIndex = (this.frameIndex + 1) % this.images.length;
            this.lastTick = timestamp - (elapsed % interval);
            this.drawFrame();
            this.syncPaper();
        }

        this.rafId = requestAnimationFrame(this.onFrame);
    }

    drawFrame() {
        const image = this.images[this.frameIndex];
        if (!image) {
            return;
        }

        const width = this.canvas.width / (window.devicePixelRatio || 1);
        const height = this.canvas.height / (window.devicePixelRatio || 1);

        this.ctx.clearRect(0, 0, width, height);
        this.ctx.drawImage(image, this.parallaxX * 0.6, this.parallaxY * 0.4, width, height);
    }

    syncPaper() {
        if (!this.paper) {
            return;
        }

        const progress = this.frameIndex / Math.max(this.images.length - 1, 1);
        this.paper.style.setProperty('--pen-progress', String(progress));
    }

    onParallax(event) {
        const rect = this.root.getBoundingClientRect();
        const x = (event.clientX - rect.left) / rect.width - 0.5;
        const y = (event.clientY - rect.top) / rect.height - 0.5;

        this.parallaxX = x * 14;
        this.parallaxY = y * 10;
        this.root.style.setProperty('--pen-tilt-x', `${y * -4}deg`);
        this.root.style.setProperty('--pen-tilt-y', `${x * 5}deg`);
    }

    onVisibility() {
        if (document.hidden) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
            return;
        }

        if (!this.rafId && this.loaded) {
            this.lastTick = performance.now();
            this.rafId = requestAnimationFrame(this.onFrame);
        }
    }

    setProcessing(active) {
        this.root.classList.toggle('is-processing', active);
        this.speed = active ? 2.4 : 1;
    }

    showGif() {
        this.canvas?.classList.remove('is-ready');
        this.gif?.classList.add('is-visible');
    }

    destroy() {
        cancelAnimationFrame(this.rafId);
        document.removeEventListener('visibilitychange', this.onVisibility);
        this.root.removeEventListener('pointermove', this.onParallax);
    }
}

function bindLivewireState(root, player) {
    const componentEl = root.closest('[wire\\:id]');
    if (!componentEl || !window.Livewire) {
        return;
    }

    const wireId = componentEl.getAttribute('wire:id');
    const component = window.Livewire.find(wireId);
    if (!component) {
        return;
    }

    player.setProcessing(Boolean(component.get('isProcessing')));

    component.watch('isProcessing', (value) => {
        player.setProcessing(Boolean(value));
    });
}

export function initBudgetScanPenHeroes() {
    document.querySelectorAll('[data-budget-scan-pen]').forEach((root) => {
        if (root.dataset.penReady) {
            return;
        }

        root.dataset.penReady = '1';
        const player = new BudgetScanPenHero(root);
        player.init().then(() => bindLivewireState(root, player));
    });
}

document.addEventListener('DOMContentLoaded', initBudgetScanPenHeroes);
document.addEventListener('livewire:navigated', initBudgetScanPenHeroes);
