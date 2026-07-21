<?php

namespace App\Filament\Widgets;

use App\Enums\QuoteCurrency;
use App\Services\Budgets\FinancialMetricsService;
use App\Support\MoneyFormatter;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

abstract class FinancialTrendChartWidget extends ChartWidget
{
    protected string $view = 'filament.widgets.financial-trend-chart';

    protected ?string $maxHeight = '300px';

    public ?string $filter = 'line';

    public string $range = '50';

    protected int | string | array $columnSpan = 'full';

    public function updatedFilter(): void
    {
        $this->cachedData = null;
    }

    public function updatedRange(): void
    {
        $this->cachedData = null;
    }

    /**
     * Evita el dispatch updateChartData de Filament al cambiar tipo/rango;
     * el remount del blade reinicia Chart.js.
     */
    public function rendering(): void
    {
        $this->dataChecksum = $this->generateDataChecksum();
    }

    protected function seriesLimit(): int
    {
        $limit = (int) $this->range;

        return in_array($limit, [5, 10, 50, 100], true) ? $limit : 50;
    }

    /**
     * @return array<string, string>
     */
    protected function getRangeOptions(): array
    {
        return [
            '5' => 'Últimos 5',
            '10' => 'Últimos 10',
            '50' => 'Últimos 50',
            '100' => 'Últimos 100',
        ];
    }

    abstract protected function seriesKey(): string;

    abstract protected function seriesLabel(): string;

    abstract protected function borderColor(): string;

    abstract protected function fillColor(): string;

    public static function canView(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, string>
     */
    protected function getFilters(): ?array
    {
        return [
            'line' => 'Línea',
            'bar' => 'Barras',
            'area' => 'Área',
            'stepped' => 'Escalonada',
            'radar' => 'Radar',
        ];
    }

    public function getDescription(): string | Htmlable | null
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        $series = app(FinancialMetricsService::class)->trendSeriesFor($user, $this->seriesLimit());
        $values = array_values($series[$this->seriesKey()] ?? []);

        if ($series['labels'] === [] || $values === []) {
            return 'Sin presupuestos emitidos para graficar.';
        }

        $count = count($values);
        $parts = ['Últimos '.$count.' presupuestos (máx. '.$this->seriesLimit().')'];

        if ($count >= 2) {
            $delta = round($values[$count - 1] - $values[0], 2);
            $parts[] = 'Variación: '.MoneyFormatter::formatSigned($delta, $series['currency']);
        }

        return implode(' · ', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'pointDeltas' => $this->pointDeltas(),
        ];
    }

    /**
     * Expuesto al Blade (Livewire a veces no mezcla getViewData con la vista custom).
     *
     * @return array<int, array{label: string, color: string, background: string, positive: bool}>
     */
    public function getPointDeltasForView(): array
    {
        return $this->pointDeltas();
    }

    /**
     * Diferencias consecutivas para labels verde/rojo entre puntos.
     *
     * @return array<int, array{label: string, color: string, background: string, positive: bool}>
     */
    protected function pointDeltas(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        $series = app(FinancialMetricsService::class)->trendSeriesFor($user, $this->seriesLimit());
        $values = array_values($series[$this->seriesKey()] ?? []);
        $currency = $series['currency'];
        $deltas = [];

        for ($i = 1, $count = count($values); $i < $count; $i++) {
            $delta = round((float) $values[$i] - (float) $values[$i - 1], 2);
            $positive = $delta >= 0;

            $deltas[] = [
                'label' => trim($this->deltaGlyph($delta).' '.MoneyFormatter::formatSigned($delta, $currency)),
                'color' => '#ffffff',
                'border' => $positive ? '#059669' : '#dc2626',
                'background' => $positive ? '#059669' : '#dc2626',
                'positive' => $positive,
            ];
        }

        return $deltas;
    }

    protected function getType(): string
    {
        return match ($this->filter) {
            'bar' => 'bar',
            'radar' => 'radar',
            default => 'line',
        };
    }

    protected function getData(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $series = app(FinancialMetricsService::class)->trendSeriesFor($user, $this->seriesLimit());
        $values = array_values($series[$this->seriesKey()] ?? []);
        /** @var QuoteCurrency $currency */
        $currency = $series['currency'];
        $chartFilter = $this->filter ?? 'line';

        if ($series['labels'] === []) {
            return [
                'datasets' => [
                    [
                        'label' => $this->seriesLabel(),
                        'data' => [],
                        'borderColor' => $this->borderColor(),
                        'backgroundColor' => $this->fillColor(),
                    ],
                ],
                'labels' => [],
            ];
        }

        $labels = [];

        foreach ($series['labels'] as $index => $dateLabel) {
            $amount = (float) ($values[$index] ?? 0);
            $labels[] = $dateLabel.' · '.MoneyFormatter::format($amount, $currency);
        }

        $dataset = [
            'label' => $this->seriesLabel(),
            'data' => $values,
            'borderColor' => $this->borderColor(),
            'backgroundColor' => match ($chartFilter) {
                'bar' => $this->borderColor(),
                'area', 'radar' => $this->fillColor(),
                default => $this->fillColor(),
            },
            'fill' => in_array($chartFilter, ['area', 'radar', 'line'], true),
            'tension' => $chartFilter === 'stepped' ? 0 : 0.35,
            'stepped' => $chartFilter === 'stepped' ? 'middle' : false,
            'pointRadius' => $chartFilter === 'bar' ? 0 : 4,
            'pointHoverRadius' => $chartFilter === 'bar' ? 0 : 6,
            'borderWidth' => $chartFilter === 'bar' ? 0 : 2,
            'borderRadius' => $chartFilter === 'bar' ? 4 : 0,
        ];

        if ($chartFilter === 'line') {
            $dataset['fill'] = false;
        }

        return [
            'datasets' => [$dataset],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): ?array
    {
        $isRadar = ($this->filter ?? 'line') === 'radar';

        $options = [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];

        if (! $isRadar) {
            $options['scales'] = [
                'x' => [
                    'ticks' => [
                        'maxRotation' => 55,
                        'minRotation' => 35,
                        'autoSkip' => true,
                        'maxTicksLimit' => 10,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                ],
            ];
        }

        return $options;
    }

    private function deltaGlyph(float $delta): string
    {
        if ($delta > 0) {
            return '▲';
        }

        if ($delta < 0) {
            return '▼';
        }

        return '';
    }
}
