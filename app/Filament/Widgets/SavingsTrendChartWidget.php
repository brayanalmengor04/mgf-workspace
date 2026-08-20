<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\OnlyOnSavingAccountsList;
use App\Services\Savings\SavingsLedgerService;
use App\Support\MoneyFormatter;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

abstract class SavingsTrendChartWidget extends ChartWidget
{
    use OnlyOnSavingAccountsList;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.savings-trend-chart';

    protected ?string $maxHeight = '320px';

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = 'line';

    public string $range = '12';

    public string $series = 'balance';

    public function updatedFilter(): void
    {
        $this->cachedData = null;
    }

    public function updatedRange(): void
    {
        $this->cachedData = null;
    }

    public function updatedSeries(): void
    {
        $this->cachedData = null;
    }

    public function rendering(): void
    {
        $this->dataChecksum = $this->generateDataChecksum();
    }

    /**
     * @return array<string, string>
     */
    protected function getRangeOptions(): array
    {
        return [
            '3' => 'Últimos 3 meses',
            '6' => 'Últimos 6 meses',
            '12' => 'Últimos 12 meses',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function getSeriesOptions(): array
    {
        return [
            'balance' => 'Saldo acumulado',
            'deposits' => 'Entradas',
            'withdrawals' => 'Retiros',
        ];
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

        $trend = $this->resolvedTrend();
        $totals = app(SavingsLedgerService::class)->analyticsForUser($user)['totals'];

        if ($trend['labels'] === []) {
            return 'Registra depósitos o retiros para ver la tendencia.';
        }

        $values = $trend[$this->series] ?? [];
        $delta = count($values) >= 2
            ? round((float) $values[array_key_last($values)] - (float) $values[array_key_first($values)], 2)
            : 0.0;

        return 'Flujo neto: '.MoneyFormatter::formatSigned($totals['net_flow'])
            .' · Variación: '.MoneyFormatter::formatSigned($delta);
    }

    /**
     * @return array<int, array{label: string, color: string, background: string, border: string, positive: bool}>
     */
    public function getPointDeltasForView(): array
    {
        if ($this->series !== 'balance') {
            return [];
        }

        $trend = $this->resolvedTrend();
        $values = array_values($trend['balance'] ?? []);
        $deltas = [];

        for ($i = 1, $count = count($values); $i < $count; $i++) {
            $delta = round((float) $values[$i] - (float) $values[$i - 1], 2);
            $positive = $delta >= 0;

            $deltas[] = [
                'label' => trim($this->deltaGlyph($delta).' '.MoneyFormatter::formatSigned($delta)),
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
            return ['datasets' => [], 'labels' => []];
        }

        $trend = $this->resolvedTrend();
        $seriesKey = $this->series;
        $values = array_values($trend[$seriesKey] ?? []);
        $chartFilter = $this->filter ?? 'line';

        if ($trend['labels'] === []) {
            return [
                'labels' => ['Sin datos'],
                'datasets' => [[
                    'label' => $this->getSeriesOptions()[$seriesKey] ?? 'Serie',
                    'data' => [0],
                    'borderColor' => '#d1d5db',
                    'backgroundColor' => '#d1d5db',
                ]],
            ];
        }

        $labels = [];

        foreach ($trend['labels'] as $index => $dateLabel) {
            $amount = (float) ($values[$index] ?? 0);
            $labels[] = $dateLabel.' · '.MoneyFormatter::format($amount);
        }

        [$borderColor, $fillColor] = match ($seriesKey) {
            'deposits' => ['#10b981', 'rgba(16, 185, 129, 0.25)'],
            'withdrawals' => ['#dc2626', 'rgba(220, 38, 38, 0.25)'],
            default => ['#059669', 'rgba(5, 150, 105, 0.25)'],
        };

        $dataset = [
            'label' => $this->getSeriesOptions()[$seriesKey] ?? 'Serie',
            'data' => $values,
            'borderColor' => $borderColor,
            'backgroundColor' => match ($chartFilter) {
                'bar' => $borderColor,
                'area', 'radar' => $fillColor,
                default => $fillColor,
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

    /**
     * @return array{
     *     labels: array<int, string>,
     *     balance: array<int, float>,
     *     deposits: array<int, float>,
     *     withdrawals: array<int, float>
     * }
     */
    protected function resolvedTrend(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [
                'labels' => [],
                'balance' => [],
                'deposits' => [],
                'withdrawals' => [],
            ];
        }

        $trend = app(SavingsLedgerService::class)->analyticsForUser($user)['trend'];
        $limit = in_array((int) $this->range, [3, 6, 12], true) ? (int) $this->range : 12;

        if ($trend['labels'] === []) {
            return $trend;
        }

        $count = count($trend['labels']);
        $offset = max(0, $count - $limit);

        return [
            'labels' => array_values(array_slice($trend['labels'], $offset)),
            'balance' => array_values(array_slice($trend['balance'], $offset)),
            'deposits' => array_values(array_slice($trend['deposits'], $offset)),
            'withdrawals' => array_values(array_slice($trend['withdrawals'], $offset)),
        ];
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
