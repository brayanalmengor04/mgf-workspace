@php
    use Filament\Widgets\View\Components\ChartWidgetComponent;
    use Illuminate\View\ComponentAttributeBag;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $filters = $this->getFilters();
    $rangeOptions = $this->getRangeOptions();
    $seriesOptions = $this->getSeriesOptions();
    $isCollapsible = $this->isCollapsible();
    $type = $this->getType();
    $filter = $this->filter ?? 'line';
    $range = $this->range ?? '12';
    $series = $this->series ?? 'balance';
    $maxHeight = $this->getMaxHeight();
    $hasMaxHeight = filled($maxHeight) && $maxHeight !== '100%';
    $cachedData = $this->getCachedData();
    $options = $this->getOptions();
    $pointDeltas = $this->getPointDeltasForView();
    $pointCount = max(count($cachedData['labels'] ?? []), count($pointDeltas) + 1);
@endphp

<x-filament-widgets::widget class="fi-wi-chart mgf-crm">
    <x-filament::section
        :heading="$heading"
        :collapsible="$isCollapsible"
    >
        <div
            class="fi-wi-financial-trend-toolbar"
            style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1rem;"
        >
            @if (filled($description))
                <p
                    class="fi-wi-financial-trend-description text-sm text-gray-500 dark:text-gray-400"
                    style="margin: 0; line-height: 1.45; overflow-wrap: anywhere; word-break: break-word;"
                >
                    {{ $description }}
                </p>
            @endif

            <div
                class="fi-wi-financial-trend-filters"
                style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;"
            >
                <x-filament::input.wrapper
                    inline-prefix
                    wire:target="series"
                    class="fi-wi-financial-trend-filter"
                    style="width: auto; min-width: 9rem; margin: 0; flex: 1 1 9rem;"
                >
                    <x-filament::input.select
                        inline-prefix
                        wire:model.live="series"
                    >
                        @foreach ($seriesOptions as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                <x-filament::input.wrapper
                    inline-prefix
                    wire:target="range"
                    class="fi-wi-financial-trend-filter"
                    style="width: auto; min-width: 8.5rem; margin: 0; flex: 1 1 8.5rem;"
                >
                    <x-filament::input.select
                        inline-prefix
                        wire:model.live="range"
                    >
                        @foreach ($rangeOptions as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                @if ($filters)
                    <x-filament::input.wrapper
                        inline-prefix
                        wire:target="filter"
                        class="fi-wi-financial-trend-filter"
                        style="width: auto; min-width: 8.5rem; margin: 0; flex: 1 1 8.5rem;"
                    >
                        <x-filament::input.select
                            inline-prefix
                            wire:model.live="filter"
                        >
                            @foreach ($filters as $value => $label)
                                <option value="{{ $value }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                @endif
            </div>
        </div>

        <div wire:key="savings-trend-{{ $series }}-{{ $filter }}-{{ $range }}">
            <div style="position: relative;">
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
                                'fi-wi-chart-canvas-ctn-no-aspect-ratio' => $hasMaxHeight,
                            ])
                    }}
                >
                    <canvas
                        x-ref="canvas"
                        @style([
                            'width: 100%',
                            'height: 100%; max-height: 100%' => ! $hasMaxHeight,
                            ('max-height: ' . e($maxHeight)) => $hasMaxHeight,
                        ])
                    ></canvas>

                    <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                    <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                    <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                    <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
                </div>

                @if (count($pointDeltas) > 0 && $pointCount > 1)
                    <div
                        aria-label="Variaciones entre meses"
                        style="
                            position: absolute;
                            left: 3%;
                            right: 1%;
                            top: 12%;
                            bottom: 28%;
                            display: flex;
                            align-items: center;
                            padding-left: calc(100% / {{ $pointCount }} / 2);
                            padding-right: calc(100% / {{ $pointCount }} / 2);
                            pointer-events: none;
                            z-index: 5;
                        "
                    >
                        @foreach ($pointDeltas as $delta)
                            <div style="flex: 1; display: flex; justify-content: center; align-items: center;">
                                <span
                                    style="
                                        color: {{ $delta['color'] }};
                                        background: {{ $delta['background'] }};
                                        border: 1px solid {{ $delta['border'] ?? $delta['background'] }};
                                        font-size: 0.72rem;
                                        font-weight: 700;
                                        line-height: 1.2;
                                        padding: 0.2rem 0.45rem;
                                        border-radius: 9999px;
                                        white-space: nowrap;
                                        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
                                    "
                                >{{ $delta['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
