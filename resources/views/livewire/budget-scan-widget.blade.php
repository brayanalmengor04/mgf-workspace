<div class="mgf-crm-ai-scan-banner">
    <div class="mgf-crm-ai-scan-banner__aurora" aria-hidden="true"></div>

    <div class="mgf-crm-ai-scan-banner__layout">
        <div class="mgf-crm-ai-scan-banner__copy">
            <div class="mgf-crm-ai-scan-banner__badge">
                <span class="mgf-crm-ai-scan-banner__badge-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/>
                        <path d="M5 19l1 3 1-3 3-1-3-1-1-3-1 3-3 1 3 1 1 3z"/>
                    </svg>
                </span>
                Asistente IA · Escaneo inteligente
            </div>

            <h2 class="mgf-crm-ai-scan-banner__title">
                Crea tu presupuesto desde una foto a mano
            </h2>

            <p class="mgf-crm-ai-scan-banner__lead">
                Sube una foto de tu presupuesto en cuaderno o papel. La IA extrae conceptos y montos
                y te devuelve un borrador editable antes de guardar.
            </p>

            <ol class="mgf-crm-ai-scan-banner__steps">
                <li><span>1</span> Toma o sube una foto</li>
                <li><span>2</span> La IA lee tu letra</li>
                <li><span>3</span> Revisas el borrador</li>
            </ol>

            <div class="mgf-crm-ai-scan-banner__panel">
                <div class="mgf-crm-ai-scan-banner__actions">
                    <label class="mgf-crm-ai-scan-banner__btn mgf-crm-ai-scan-banner__btn--camera">
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            capture="environment"
                            wire:model="scanImage"
                            wire:loading.attr="disabled"
                            style="display:none;"
                        >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                        Tomar foto
                    </label>

                    <label class="mgf-crm-ai-scan-banner__btn">
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            wire:model="scanImage"
                            wire:loading.attr="disabled"
                            style="display:none;"
                        >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                            <path d="M4 20h16a2 2 0 002-2V6a2 2 0 00-2-2H8l-2 2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Galería
                    </label>

                    <button
                        type="button"
                        class="mgf-crm-ai-scan-banner__btn mgf-crm-ai-scan-banner__btn--primary"
                        wire:click="processScan"
                        wire:loading.attr="disabled"
                        @disabled($isProcessing || ! $scanImage)
                    >
                        <span wire:loading.remove wire:target="processScan,scanImage">Analizar con IA</span>
                        <span wire:loading wire:target="processScan,scanImage">Analizando…</span>
                    </button>
                </div>

                @if ($scanImage)
                    <p class="mgf-crm-ai-scan-banner__status mgf-crm-ai-scan-banner__status--ok">Imagen lista — pulsa «Analizar con IA».</p>
                @endif

                @if ($errorMessage)
                    <p class="mgf-crm-ai-scan-banner__status mgf-crm-ai-scan-banner__status--error">{{ $errorMessage }}</p>
                @endif

                @error('scanImage')
                    <p class="mgf-crm-ai-scan-banner__status mgf-crm-ai-scan-banner__status--error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div
            class="mgf-crm-ai-scan-banner__hero"
            wire:ignore
            data-budget-scan-pen
            x-data="{ busy: @entangle('isProcessing') }"
            x-effect="$el.classList.toggle('is-processing', busy)"
        >
            <div class="mgf-crm-ai-scan-banner__hero-stage" aria-hidden="true">
                <div class="mgf-crm-ai-scan-banner__hero-glow"></div>
                <div class="mgf-crm-ai-scan-banner__hero-ring mgf-crm-ai-scan-banner__hero-ring--outer"></div>
                <div class="mgf-crm-ai-scan-banner__hero-ring mgf-crm-ai-scan-banner__hero-ring--inner"></div>

                <div class="mgf-crm-ai-scan-banner__paper" data-pen-paper>
                    <div class="mgf-crm-ai-scan-banner__paper-header">
                        <span></span><span></span><span></span>
                    </div>
                    <div class="mgf-crm-ai-scan-banner__paper-line"></div>
                    <div class="mgf-crm-ai-scan-banner__paper-line"></div>
                    <div class="mgf-crm-ai-scan-banner__paper-line"></div>
                    <div class="mgf-crm-ai-scan-banner__paper-line mgf-crm-ai-scan-banner__paper-line--short"></div>
                </div>

                <div class="mgf-crm-ai-scan-banner__pen-visual">
                    <img
                        class="mgf-crm-ai-scan-banner__hero-gif"
                        data-pen-gif
                        src="{{ asset('animations/budget-scan-pen/hero.gif') }}"
                        alt=""
                        width="720"
                        height="405"
                        loading="lazy"
                        decoding="async"
                    >

                    <canvas
                        class="mgf-crm-ai-scan-banner__hero-canvas"
                        data-pen-canvas
                        aria-label="Animación del bolígrafo desmontándose mientras la IA lee un presupuesto escrito a mano"
                    ></canvas>
                </div>

                <span class="mgf-crm-ai-scan-banner__sparkle mgf-crm-ai-scan-banner__sparkle--1">✦</span>
                <span class="mgf-crm-ai-scan-banner__sparkle mgf-crm-ai-scan-banner__sparkle--2">✦</span>
                <span class="mgf-crm-ai-scan-banner__sparkle mgf-crm-ai-scan-banner__sparkle--3">✦</span>

                <div class="mgf-crm-ai-scan-banner__hero-chip">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2l2.2 6.8L21 11l-6.8 2.2L12 20l-2.2-6.8L3 11l6.8-2.2L12 2z"/>
                    </svg>
                    <span class="mgf-crm-ai-scan-banner__hero-chip-label">IA activa</span>
                    <span class="mgf-crm-ai-scan-banner__hero-chip-label mgf-crm-ai-scan-banner__hero-chip-label--busy">Leyendo tu letra…</span>
                </div>
            </div>
        </div>
    </div>
</div>
