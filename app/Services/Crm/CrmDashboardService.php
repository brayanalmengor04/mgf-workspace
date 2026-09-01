<?php

namespace App\Services\Crm;

use App\Enums\BudgetCategoryType;
use App\Enums\BudgetStatus;
use App\Enums\QuoteCurrency;
use App\Enums\QuoteStatus;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\CalendarEvent;
use App\Models\Quote;
use App\Models\User;
use App\Services\Budgets\FinancialMetricsService;
use App\Support\MoneyFormatter;

class CrmDashboardService
{
    public function __construct(
        private FinancialMetricsService $financialMetrics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $overview = $this->financialMetrics->overviewFor($user);
        $savings = $this->financialMetrics->savingsLedgerSummaryFor($user);
        $trend = $this->financialMetrics->trendSeriesFor($user, 12);
        $currency = $overview['currency'];

        $pendingAmount = max(0, round((float) $overview['paid_amount'], 2));
        $totalAllocated = $overview['has_issued']
            ? round((float) $overview['net_income'] - (float) $overview['available_balance'], 2)
            : 0.0;

        $categoryBreakdown = $this->categoryBreakdownFor($user, $overview);
        $paymentProgress = $this->paymentProgressFor($overview);
        $pendingItemsSum = $this->pendingItemsSumFor($user);
        $budgetStats = $this->budgetListStatsFor($user, $currency);
        $quoteStats = $this->quoteStatsFor($user);

        return [
            'currency' => $currency,
            'stats' => $this->flatStatsFromSections($this->buildStatSections($overview, $savings, $currency, $pendingItemsSum, $budgetStats, $quoteStats)),
            'stat_sections' => $this->buildStatSections($overview, $savings, $currency, $pendingItemsSum, $budgetStats, $quoteStats),
            'trend' => $trend,
            'trend_point_deltas' => [
                'available_balance' => $this->trendPointDeltasFor($trend, 'available_balance'),
                'net_income' => $this->trendPointDeltasFor($trend, 'net_income'),
                'paid_amount' => $this->trendPointDeltasFor($trend, 'paid_amount'),
            ],
            'category_breakdown' => $categoryBreakdown,
            'category_chart' => [
                'labels' => array_column($categoryBreakdown, 'label'),
                'data' => array_column($categoryBreakdown, 'amount'),
                'colors' => array_column($categoryBreakdown, 'color'),
            ],
            'payment_progress' => $paymentProgress,
            'payment_split' => [
                'paid' => round((float) $overview['paid_amount'], 2),
                'pending' => round($pendingItemsSum, 2),
            ],
            'upcoming_events' => $this->upcomingEventsFor($user),
            'pending_budget_items' => $this->pendingBudgetItemsFor($user, $currency),
            'recent_budgets' => $this->recentBudgetsFor($user, $currency),
            'recent_quotes' => $this->recentQuotesFor($user, $currency),
            'budget_list_stats' => $budgetStats,
            'quote_stats' => $quoteStats,
        ];
    }

    /**
     * @param  array<string, mixed>  $overview
     * @param  array<string, mixed>  $savings
     * @param  array<string, mixed>  $budgetStats
     * @param  array<string, int>  $quoteStats
     * @return array<int, array{title: string, subtitle: string, stats: array<int, array<string, mixed>>}>
     */
    private function buildStatSections(
        array $overview,
        array $savings,
        QuoteCurrency $currency,
        float $pendingItemsSum,
        array $budgetStats,
        array $quoteStats,
    ): array {
        $hasIssued = (bool) $overview['has_issued'];
        $periodHint = $hasIssued ? 'Último presupuesto emitido' : 'Sin presupuestos emitidos';

        $replenishmentTone = $savings['pending_replenishment'] > 0 ? 'warning' : 'default';
        $availableTone = $overview['available_balance'] >= 0 ? 'success' : 'danger';

        return [
            [
                'title' => 'Resumen del período',
                'subtitle' => $periodHint,
                'stats' => [
                    [
                        'label' => 'Saldo disponible',
                        'value' => MoneyFormatter::format($overview['available_balance'], $currency),
                        'delta' => $this->formatDelta($overview['available_delta'], $currency, 'vs período previo'),
                        'sparkline' => $overview['available_sparkline'] ?? [],
                        'value_tone' => $availableTone,
                    ],
                    [
                        'label' => 'Ingresos netos',
                        'value' => MoneyFormatter::format($overview['net_income'], $currency),
                        'delta' => $this->formatIncomeDelta($overview, $currency),
                        'sparkline' => $overview['net_income_sparkline'] ?? [],
                        'value_tone' => 'default',
                    ],
                    [
                        'label' => 'Cumplimiento de pagos',
                        'value' => $overview['payment_compliance_percent'].'%',
                        'delta' => [
                            'text' => $overview['paid_items_count'].' de '.$overview['total_items_count'].' ítems pagados',
                            'tone' => $overview['payment_compliance_percent'] >= 70 ? 'up' : ($overview['payment_compliance_percent'] >= 40 ? 'neutral' : 'down'),
                        ],
                        'sparkline' => $overview['payment_sparkline'] ?? [],
                        'value_tone' => $overview['payment_compliance_percent'] >= 70 ? 'success' : ($overview['payment_compliance_percent'] >= 40 ? 'warning' : 'danger'),
                    ],
                    [
                        'label' => 'Pagado en presupuestos',
                        'value' => MoneyFormatter::format($overview['paid_amount'], $currency),
                        'delta' => [
                            'text' => $overview['paid_plans_count'].' presupuesto(s) con pagos',
                            'tone' => 'neutral',
                        ],
                        'sparkline' => $overview['payment_sparkline'] ?? [],
                        'value_tone' => 'success',
                    ],
                ],
            ],
            [
                'title' => 'Salud financiera',
                'subtitle' => 'Ahorro, gastos y riesgos del plan',
                'stats' => [
                    [
                        'label' => 'Ahorro planificado',
                        'value' => MoneyFormatter::format($overview['savings_amount'], $currency),
                        'delta' => [
                            'text' => $hasIssued
                                ? $overview['savings_percent'].'% del ingreso · Fijos '.$overview['fixed_expenses_percent'].'%'
                                : 'Sin datos del período',
                            'tone' => 'neutral',
                        ],
                        'value_tone' => 'success',
                    ],
                    [
                        'label' => 'Gastos fijos',
                        'value' => MoneyFormatter::format($overview['fixed_expenses_amount'], $currency),
                        'delta' => [
                            'text' => $overview['fixed_expenses_percent'].'% del ingreso neto',
                            'tone' => 'neutral',
                        ],
                        'value_tone' => 'default',
                    ],
                    [
                        'label' => 'Saldo en cuentas',
                        'value' => MoneyFormatter::format($savings['total_balance'], $currency),
                        'delta' => [
                            'text' => $savings['active_accounts'] > 0
                                ? $savings['active_accounts'].' cuenta(s) activa(s)'
                                : 'Sin cuentas de ahorro',
                            'tone' => 'neutral',
                        ],
                        'value_tone' => 'default',
                    ],
                    [
                        'label' => 'Por reponer',
                        'value' => MoneyFormatter::format($savings['pending_replenishment'], $currency),
                        'delta' => [
                            'text' => $savings['pending_replenishment'] > 0
                                ? 'Retiros pendientes de reposición'
                                : 'Sin retiros pendientes',
                            'tone' => $savings['pending_replenishment'] > 0 ? 'down' : 'up',
                        ],
                        'value_tone' => $replenishmentTone,
                    ],
                    [
                        'label' => 'Presupuestos excedidos',
                        'value' => (string) $overview['exceeded_plans_count'],
                        'delta' => [
                            'text' => $overview['exceeded_plans_count'] > 0
                                ? 'Emitidos con saldo negativo'
                                : 'Ningún presupuesto en rojo',
                            'tone' => $overview['exceeded_plans_count'] > 0 ? 'down' : 'up',
                        ],
                        'value_tone' => $overview['exceeded_plans_count'] > 0 ? 'danger' : 'success',
                    ],
                ],
            ],
            [
                'title' => 'Pipeline operativo',
                'subtitle' => 'Pendientes, presupuestos y cotizaciones',
                'stats' => [
                    [
                        'label' => 'Pendiente por pagar',
                        'value' => MoneyFormatter::format($pendingItemsSum, $currency),
                        'delta' => [
                            'text' => 'Ítems sin pagar en emitidos',
                            'tone' => $pendingItemsSum > 0 ? 'down' : 'up',
                        ],
                        'value_tone' => $pendingItemsSum > 0 ? 'warning' : 'success',
                    ],
                    [
                        'label' => 'Presupuestos emitidos',
                        'value' => (string) $overview['issued_plans_count'],
                        'delta' => [
                            'text' => $budgetStats['drafts'].' borrador(es)',
                            'tone' => 'neutral',
                        ],
                        'value_tone' => 'default',
                    ],
                    [
                        'label' => 'Ingresos acumulados',
                        'value' => MoneyFormatter::format($overview['total_income'], $currency),
                        'delta' => [
                            'text' => 'Gastos: '.MoneyFormatter::format($overview['total_expenses'], $currency),
                            'tone' => 'neutral',
                        ],
                        'value_tone' => 'default',
                    ],
                    [
                        'label' => 'Cotizaciones',
                        'value' => (string) $quoteStats['total'],
                        'delta' => [
                            'text' => $quoteStats['drafts'].' borrador · '.$quoteStats['issued'].' emitida(s)',
                            'tone' => 'neutral',
                        ],
                        'value_tone' => 'default',
                    ],
                    [
                        'label' => 'Cotizaciones del mes',
                        'value' => (string) $quoteStats['month_count'],
                        'delta' => [
                            'text' => $quoteStats['cancelled'].' anulada(s)',
                            'tone' => 'neutral',
                        ],
                        'value_tone' => 'default',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array{title: string, subtitle: string, stats: array<int, array<string, mixed>>}>  $sections
     * @return array<int, array<string, mixed>>
     */
    private function flatStatsFromSections(array $sections): array
    {
        return collect($sections)
            ->flatMap(fn (array $section): array => $section['stats'] ?? [])
            ->values()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function quoteStatsFor(User $user): array
    {
        $query = Quote::query()->forUser($user);

        return [
            'total' => (clone $query)->count(),
            'drafts' => (clone $query)->where('status', QuoteStatus::Draft)->count(),
            'issued' => (clone $query)->where('status', QuoteStatus::Issued)->count(),
            'cancelled' => (clone $query)->where('status', QuoteStatus::Cancelled)->count(),
            'month_count' => (clone $query)->where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $overview
     * @return array{text: string, tone: string}
     */
    private function formatIncomeDelta(array $overview, QuoteCurrency $currency): array
    {
        $delta = $overview['net_income_delta'];
        $percent = $overview['net_income_delta_percent'];

        if ($delta === null) {
            return [
                'text' => $overview['has_issued'] ? 'Último presupuesto emitido' : 'Sin presupuestos emitidos',
                'tone' => 'neutral',
            ];
        }

        $sign = $delta > 0 ? '+' : '';
        $money = $sign.MoneyFormatter::format($delta, $currency);
        $percentText = $percent !== null
            ? ' ('.($percent > 0 ? '+' : '').$percent.'%)'
            : '';

        return [
            'text' => ($delta > 0 ? 'Subiste ' : ($delta < 0 ? 'Bajaste ' : 'Sin cambio ')).$money.$percentText,
            'tone' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral'),
        ];
    }

    /**
     * @param  array<string, mixed>  $trend
     * @return array<int, array{label: string, color: string, background: string, border: string, positive: bool}>
     */
    public function trendPointDeltasFor(array $trend, string $metricKey): array
    {
        $values = array_values($trend[$metricKey] ?? []);
        $currency = $trend['currency'] ?? QuoteCurrency::Usd;
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

    /**
     * @return array<string, mixed>
     */
    public function budgetListStatsFor(User $user, ?QuoteCurrency $currency = null): array
    {
        $plans = BudgetPlan::query()->forUser($user);
        $issued = (clone $plans)->where('status', BudgetStatus::Issued)->count();
        $drafts = (clone $plans)->where('status', BudgetStatus::Draft)->count();
        $total = (clone $plans)->count();

        $pendingItemsQuery = BudgetPlanItem::query()
            ->where('is_paid', false)
            ->where('amount', '>', 0)
            ->whereHas('budgetPlan', fn ($query) => $query->forUser($user)->where('status', BudgetStatus::Issued));

        $pendingItems = (clone $pendingItemsQuery)->sum('amount');
        $pendingItemsCount = (clone $pendingItemsQuery)->count();

        $currency = $currency ?? QuoteCurrency::Usd;

        return [
            'total' => $total,
            'issued' => $issued,
            'drafts' => $drafts,
            'pending_items_amount' => round((float) $pendingItems, 2),
            'pending_items_count' => $pendingItemsCount,
            'pending_items_formatted' => MoneyFormatter::format((float) $pendingItems, $currency),
        ];
    }

    /**
     * @param  array<string, mixed>  $overview
     * @return array<int, array{label: string, amount: float, percent: float, color: string}>
     */
    private function categoryBreakdownFor(User $user, array $overview): array
    {
        if (! $overview['has_issued']) {
            return [];
        }

        $netIncome = max((float) $overview['net_income'], 0.01);

        return [
            [
                'label' => 'Gastos fijos',
                'amount' => (float) $overview['fixed_expenses_amount'],
                'percent' => round(((float) $overview['fixed_expenses_amount'] / $netIncome) * 100, 1),
                'color' => '#64748b',
            ],
            [
                'label' => 'Ahorros',
                'amount' => (float) $overview['savings_amount'],
                'percent' => (float) $overview['savings_percent'],
                'color' => '#0d9488',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overview
     * @return array<int, array{label: string, percent: float}>
     */
    private function paymentProgressFor(array $overview): array
    {
        return [
            [
                'label' => 'Ítems pagados',
                'percent' => (float) $overview['payment_compliance_percent'],
            ],
            [
                'label' => 'Ahorro planificado',
                'percent' => (float) $overview['savings_percent'],
            ],
            [
                'label' => 'Gastos fijos',
                'percent' => (float) $overview['fixed_expenses_percent'],
            ],
        ];
    }

    private function pendingItemsSumFor(User $user): float
    {
        return (float) BudgetPlanItem::query()
            ->where('is_paid', false)
            ->where('amount', '>', 0)
            ->whereHas('budgetPlan', fn ($query) => $query->forUser($user)->where('status', BudgetStatus::Issued))
            ->sum('amount');
    }

    /**
     * @return array<int, array{date: string, title: string, meta: string}>
     */
    private function upcomingEventsFor(User $user): array
    {
        return CalendarEvent::query()
            ->where('user_id', $user->id)
            ->where('start_date', '>=', now()->startOfDay())
            ->orderBy('start_date')
            ->limit(5)
            ->get()
            ->map(fn (CalendarEvent $event): array => [
                'date' => $event->start_date?->translatedFormat('d M') ?? '—',
                'title' => (string) $event->title,
                'meta' => $event->amount !== null
                    ? MoneyFormatter::format((float) $event->amount)
                    : ($event->description ? mb_substr((string) $event->description, 0, 48) : 'Sin detalle'),
            ])
            ->all();
    }

    /**
     * @return array<int, array{concept: string, amount: string, budget: string, url: string|null}>
     */
    private function pendingBudgetItemsFor(User $user, QuoteCurrency $currency): array
    {
        return BudgetPlanItem::query()
            ->with('budgetPlan:id,budget_number,title')
            ->where('is_paid', false)
            ->where('amount', '>', 0)
            ->whereHas('budgetPlan', fn ($query) => $query->forUser($user)->where('status', BudgetStatus::Issued))
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (BudgetPlanItem $item) use ($currency): array {
                $plan = $item->budgetPlan;

                return [
                    'concept' => (string) $item->concept,
                    'amount' => MoneyFormatter::format((float) $item->amount, $currency),
                    'budget' => $plan?->budget_number ?? 'Presupuesto',
                    'category' => $item->category_type instanceof BudgetCategoryType
                        ? $item->category_type->label()
                        : (string) $item->category_type,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function recentBudgetsFor(User $user, QuoteCurrency $currency): array
    {
        return BudgetPlan::query()
            ->forUser($user)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (BudgetPlan $plan): array => [
                'id' => $plan->id,
                'number' => (string) $plan->budget_number,
                'title' => (string) ($plan->title ?: 'Sin título'),
                'status' => $plan->status->label(),
                'available' => MoneyFormatter::format((float) $plan->remaining_balance, QuoteCurrency::resolve($plan->currency)),
                'period' => $plan->period->label(),
                'url' => \App\Filament\Resources\BudgetPlans\BudgetPlanResource::getUrl('view', ['record' => $plan]),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function recentQuotesFor(User $user, QuoteCurrency $currency): array
    {
        return Quote::query()
            ->forUser($user)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Quote $quote): array => [
                'id' => $quote->id,
                'number' => (string) $quote->quote_number,
                'client' => (string) ($quote->recipient_name ?: 'Sin cliente'),
                'status' => $quote->status instanceof QuoteStatus ? $quote->status->label() : (string) $quote->status,
                'total' => MoneyFormatter::format((float) $quote->total, QuoteCurrency::resolve($quote->currency)),
                'url' => \App\Filament\Resources\Quotes\QuoteResource::getUrl('view', ['record' => $quote]),
            ])
            ->all();
    }

    /**
     * @return array{text: string, tone: string}
     */
    private function formatDelta(?float $delta, QuoteCurrency $currency, string $suffix): array
    {
        if ($delta === null) {
            return ['text' => 'Sin comparación', 'tone' => 'neutral'];
        }

        $formatted = MoneyFormatter::formatSigned($delta, $currency);

        return [
            'text' => "{$formatted} {$suffix}",
            'tone' => $delta >= 0 ? 'up' : 'down',
        ];
    }

    /**
     * @return array{text: string, tone: string}
     */
    private function formatPercentDelta(?float $percent): array
    {
        if ($percent === null) {
            return ['text' => 'Sin comparación', 'tone' => 'neutral'];
        }

        $sign = $percent >= 0 ? '+' : '';

        return [
            'text' => "{$sign}{$percent}% vs período previo",
            'tone' => $percent >= 0 ? 'up' : 'down',
        ];
    }
}
