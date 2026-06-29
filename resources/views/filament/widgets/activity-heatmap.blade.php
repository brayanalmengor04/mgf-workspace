<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('filament-activity-log::activity.widgets.heatmap.heading') }}
        </x-slot>

        @php
            $heatmap = $this->getData();
            $data = $heatmap['data'] ?? [];
            $max = max(1, (int) ($heatmap['max'] ?? 1));
            $startDate = now()->subDays(365)->startOfWeek();
            $endDate = now();

            $months = [];
            $currentMonth = $startDate->format('M');
            $months[] = ['name' => $currentMonth, 'week_index' => 0];
            $lastLabelWeek = 0;

            $dt = $startDate->copy();
            for ($weekIndex = 0; $weekIndex < 52; $weekIndex++) {
                $month = $dt->addWeek()->format('M');
                if ($month !== $currentMonth) {
                    if (($weekIndex - $lastLabelWeek) >= 4) {
                        $months[] = ['name' => $month, 'week_index' => $weekIndex];
                        $lastLabelWeek = $weekIndex;
                        $currentMonth = $month;
                    }
                }
            }

            $cellSize = 11;
            $gap = 3;
            $weekWidth = $cellSize + $gap;
        @endphp

        <div style="overflow-x: auto; padding: 1rem 0;">
            <div style="position: relative; height: 16px; margin-bottom: 8px; font-size: 10px; font-weight: 600; color: #9ca3af;">
                @foreach ($months as $month)
                    <div style="position: absolute; left: {{ $month['week_index'] * $weekWidth }}px; white-space: nowrap;">
                        {{ $month['name'] }}
                    </div>
                @endforeach
            </div>

            <div style="
                display: grid;
                grid-template-rows: repeat(7, {{ $cellSize }}px);
                grid-auto-flow: column;
                gap: {{ $gap }}px;
                width: max-content;
            ">
                @foreach (range(0, 52) as $week)
                    @foreach (range(0, 6) as $day)
                        @php
                            $currentDate = $startDate->copy()->addWeeks($week)->addDays($day);
                            $dateString = $currentDate->toDateString();
                            $count = (int) ($data[$dateString] ?? 0);
                            $intensity = $count > 0 ? (int) ceil(($count / $max) * 4) : 0;

                            $bg = match ($intensity) {
                                0 => 'rgba(128, 128, 128, 0.12)',
                                1 => '#86efac',
                                2 => '#4ade80',
                                3 => '#22c55e',
                                4 => '#15803d',
                                default => 'rgba(128, 128, 128, 0.12)',
                            };

                            $tooltip = __('filament-activity-log::activity.widgets.heatmap.tooltip', [
                                'count' => $count,
                                'date' => $currentDate->format('M j, Y'),
                            ]);
                        @endphp

                        @if ($currentDate <= $endDate)
                            <div
                                title="{{ $count }} {{ $tooltip }}"
                                style="
                                    width: {{ $cellSize }}px;
                                    height: {{ $cellSize }}px;
                                    border-radius: 2px;
                                    background-color: {{ $bg }};
                                    transition: transform 0.15s ease;
                                    cursor: pointer;
                                "
                                onmouseover="this.style.transform='scale(1.35)'; this.style.zIndex='10';"
                                onmouseout="this.style.transform='scale(1)'; this.style.zIndex='1';"
                            ></div>
                        @else
                            <div style="width: {{ $cellSize }}px; height: {{ $cellSize }}px;"></div>
                        @endif
                    @endforeach
                @endforeach
            </div>

            <div style="margin-top: 1rem; display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; font-size: 0.75rem; color: #9ca3af;">
                <span>{{ __('filament-activity-log::activity.widgets.heatmap.less') }}</span>
                <div style="width: {{ $cellSize }}px; height: {{ $cellSize }}px; border-radius: 2px; background-color: rgba(128, 128, 128, 0.12);"></div>
                <div style="width: {{ $cellSize }}px; height: {{ $cellSize }}px; border-radius: 2px; background-color: #86efac;"></div>
                <div style="width: {{ $cellSize }}px; height: {{ $cellSize }}px; border-radius: 2px; background-color: #4ade80;"></div>
                <div style="width: {{ $cellSize }}px; height: {{ $cellSize }}px; border-radius: 2px; background-color: #22c55e;"></div>
                <div style="width: {{ $cellSize }}px; height: {{ $cellSize }}px; border-radius: 2px; background-color: #15803d;"></div>
                <span>{{ __('filament-activity-log::activity.widgets.heatmap.more') }}</span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
