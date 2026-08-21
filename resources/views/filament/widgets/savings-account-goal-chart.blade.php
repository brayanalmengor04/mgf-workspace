@php
    use Filament\Widgets\View\Components\ChartWidgetComponent;
    use Illuminate\View\ComponentAttributeBag;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $isCollapsible = $this->isCollapsible();
    $type = $this->getType();
    $maxHeight = $this->getMaxHeight();
    $hasMaxHeight = filled($maxHeight) && $maxHeight !== '100%';
    $cachedData = $this->getCachedData();
    $options = $this->getOptions();
    $centerPercent = $this->centerPercent ?? null;
    $accountName = $accountName ?? null;
    $goalLabel = $goalLabel ?? null;
    $balanceFormatted = $balanceFormatted ?? null;
    $goalFormatted = $goalFormatted ?? null;
    $remainingFormatted = $remainingFormatted ?? null;
    $missingPercent = $missingPercent ?? null;
    $goalProjection = $goalProjection ?? null;
@endphp

<x-filament-widgets::widget class="fi-wi-chart fi-wi-savings-goal-hero">
    <x-filament::section :heading="$heading" :collapsible="$isCollapsible">
        <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem;">
            @if ($accountName)
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300" style="margin: 0; text-align: center;">
                    {{ $accountName }} · {{ $goalLabel }}
                </p>
            @endif

            <div
                style="
                    position: relative;
                    width: min(100%, 22rem);
                    height: 22rem;
                    margin: 0 auto;
                "
            >
                <div
                    x-load
                    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                    data-chart-type="{{ $type }}"
                    x-data="chart({
                                cachedData: @js($cachedData),
                                options: @js($options),
                                type: @js($type),
                            })"
                    {{
                        (new ComponentAttributeBag)
                            ->color(ChartWidgetComponent::class, $color)
                            ->class([
                                'fi-wi-chart-canvas-ctn',
                                'fi-wi-chart-canvas-ctn-no-aspect-ratio',
                            ])
                    }}
                    style="width: 100%; height: 100%;"
                >
                    <canvas
                        x-ref="canvas"
                        style="width: 100%; height: 100%; max-height: 100%;"
                    ></canvas>

                    <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                    <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                    <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                    <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
                </div>

                @if ($centerPercent !== null)
                    <div
                        style="
                            position: absolute;
                            inset: 0;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            pointer-events: none;
                            padding-bottom: 2rem;
                        "
                    >
                        <div style="text-align: center;">
                            <div
                                class="font-bold text-gray-950 dark:text-white"
                                style="font-size: clamp(2.5rem, 8vw, 3.75rem); line-height: 1;"
                            >
                                {{ number_format((float) $centerPercent, 1) }}%
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400" style="margin-top: 0.35rem;">
                                cumplido
                            </div>
                            @if ($remainingFormatted && (float) $centerPercent < 100)
                                <div class="text-sm font-semibold text-amber-600 dark:text-amber-400" style="margin-top: 0.5rem;">
                                    Faltan {{ $remainingFormatted }}
                                </div>
                                @if ($missingPercent !== null)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        ({{ number_format((float) $missingPercent, 1) }}% por alcanzar)
                                    </div>
                                @endif
                            @elseif ((float) $centerPercent >= 100)
                                <div class="text-sm font-semibold text-emerald-600 dark:text-emerald-400" style="margin-top: 0.5rem;">
                                    Meta cumplida
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if (filled($description))
                <p
                    class="text-sm text-gray-600 dark:text-gray-300"
                    style="margin: 0; line-height: 1.5; text-align: center; max-width: 32rem;"
                >
                    @if ($balanceFormatted && $goalFormatted)
                        <strong>{{ $balanceFormatted }}</strong> de <strong>{{ $goalFormatted }}</strong>
                    @else
                        {{ $description }}
                    @endif
                </p>
            @endif

            @if (filled($goalProjection['label_detail'] ?? null))
                <div
                    style="
                        width: min(100%, 36rem);
                        padding: 0.875rem 1rem;
                        border-radius: 0.75rem;
                        border: 1px solid rgb(229 231 235);
                        background: rgb(249 250 251);
                        text-align: center;
                    "
                    class="dark:border-gray-700 dark:bg-gray-900/40"
                >
                    @if (filled($goalProjection['estimated_date'] ?? null) && ($goalProjection['confidence'] ?? 'none') !== 'complete')
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400" style="margin: 0 0 0.35rem;">
                            Proyección {{ $goalProjection['cadence_label'] ?? 'Quincenal' }}
                        </p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white" style="margin: 0 0 0.35rem;">
                            Meta hacia {{ $goalProjection['estimated_date'] }}
                        </p>
                    @endif
                    <p class="text-sm text-gray-600 dark:text-gray-300" style="margin: 0; line-height: 1.5;">
                        {{ $goalProjection['label_detail'] }}
                    </p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
