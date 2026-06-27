@php
    $iframeSrc = 'data:text/html;charset=utf-8;base64,'.base64_encode($this->getDocumentHtml());
@endphp

<x-filament-panels::page>
    <style>
        .budget-plan-preview-root {
            width: 100%;
            max-width: none;
        }

        .budget-plan-preview-canvas {
            width: 100%;
            box-sizing: border-box;
        }

        .budget-plan-preview-stage {
            display: flex;
            width: 100%;
            min-width: 0;
            justify-content: center;
            align-items: flex-start;
        }

        .budget-plan-preview-paper {
            flex-shrink: 0;
        }
    </style>

    <div
        class="budget-plan-preview-root"
        x-data="{
            paperWidth: 816,
            paperHeight: 1056,
            scale: 1,
            updateScale() {
                const width = this.$refs.canvas?.clientWidth ?? 0;
                const padding = 48;
                const available = Math.max(width - padding, 320);
                this.scale = Math.min(1, available / this.paperWidth);
            },
            scaledWidth() {
                return Math.round(this.paperWidth * this.scale);
            },
            scaledHeight() {
                return Math.round(this.paperHeight * this.scale);
            },
        }"
        x-init="
            $nextTick(() => {
                updateScale();
                if ($refs.canvas) {
                    new ResizeObserver(() => updateScale()).observe($refs.canvas);
                }
            });
        "
    >
        <p class="mb-4 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
            Vista previa con datos de ejemplo. Usa el estilo y color <strong>guardados</strong> de este presupuesto.
            Si acabas de cambiar el diseño, guarda antes de previsualizar.
        </p>

        <div
            x-ref="canvas"
            class="budget-plan-preview-canvas overflow-auto rounded-xl border border-gray-200 bg-zinc-300/90 p-6 dark:border-gray-700 dark:bg-zinc-900/90"
            style="max-height: calc(100vh - 14rem);"
        >
            <div class="budget-plan-preview-stage">
                <div
                    class="budget-plan-preview-paper overflow-hidden rounded-sm bg-white shadow-2xl ring-1 ring-black/10"
                    :style="`width: ${scaledWidth()}px; height: ${scaledHeight()}px; margin: 0 auto;`"
                >
                    <iframe
                        title="Vista previa del estilo PDF"
                        src="{{ $iframeSrc }}"
                        class="block border-0 bg-white"
                        :style="`width: ${paperWidth}px; height: ${paperHeight}px; transform: scale(${scale}); transform-origin: top left;`"
                    ></iframe>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
