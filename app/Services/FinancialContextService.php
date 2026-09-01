<?php

namespace App\Services;

use App\Enums\BudgetStatus;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\Quote;
use App\Models\User;
use App\Services\Budgets\FinancialMetricsService;
use App\Services\Savings\SavingsLedgerService;
use Carbon\Carbon;

class FinancialContextService
{
    /**
     * Generates a 4-month summary of the user's own budget plans (never other users' data).
     */
    public function getFourMonthSummary(User $user): string
    {
        $startDate = Carbon::now()->subMonths(4);

        $budgets = BudgetPlan::query()
            ->with('items')
            ->where('created_by', $user->id)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($budgets->isEmpty()) {
            return "El usuario no tiene presupuestos en los últimos 4 meses.";
        }

        $summary = "Resumen de Presupuestos (Últimos 4 meses):\n";

        foreach ($budgets as $budget) {
            $period = $budget->period->value ?? $budget->period;
            $summary .= "- Presupuesto '{$budget->title}' [Comprobante: {$budget->budget_number}] ({$period}): Ingresos Netos: {$budget->net_income}, Asignado: {$budget->total_allocated}, Restante: {$budget->remaining_balance}\n";
            $summary .= "  Ítems:\n";
            
            foreach ($budget->items as $item) {
                $status = $item->is_paid ? 'Pagado' : 'Pendiente';
                $notes = $item->notes ? " (Nota: {$item->notes})" : '';
                $catType = $item->category_type->value ?? $item->category_type;
                $summary .= "    * [{$catType}] {$item->concept}: \${$item->amount} - Estado: {$status}{$notes}\n";
            }
            $summary .= "\n";
        }

        return $summary;
    }

    /**
     * Resumen de cuentas de ahorro del usuario para el asistente IA.
     */
    public function getSavingsSummary(User $user): string
    {
        $analytics = app(SavingsLedgerService::class)->analyticsForUser($user);
        $summary = $analytics['summary'];
        $totals = $analytics['totals'];
        $gap = $analytics['gap'];

        if ($summary['active_accounts'] === 0) {
            return "CUENTAS DE AHORRO:\nEl usuario no tiene cuentas de ahorro activas.";
        }

        $text = "CUENTAS DE AHORRO (solo datos de este usuario):\n";
        $text .= '- Saldo total acumulado: $'.number_format($summary['total_balance'], 2)."\n";
        $text .= '- Pendiente por reponer: $'.number_format($summary['pending_replenishment'], 2)."\n";
        $text .= '- Cuentas activas: '.$summary['active_accounts']."\n";
        $text .= '- Entradas totales (aperturas + depósitos + reposiciones): $'
            .number_format($totals['openings'] + $totals['deposits'] + $totals['replenishments'], 2)."\n";
        $text .= '- Retiros totales: $'.number_format($totals['withdrawals'], 2)."\n";
        $text .= '- Flujo neto: $'.number_format($totals['net_flow'], 2)."\n";

        if ($gap['pending'] > 0) {
            $text .= '- Si repusiera todo lo pendiente, el saldo sería $'
                .number_format($gap['target_if_replenished'], 2)."\n";
        } else {
            $text .= "- No tiene retiros pendientes de reposición.\n";
        }

        $text .= "\nDetalle por cuenta:\n";

        foreach ($analytics['accounts'] as $account) {
            $text .= "* {$account['name']}: saldo $".number_format($account['current_balance'], 2);

            if ($account['pending_replenishment'] > 0) {
                $text .= ', pendiente de reponer $'.number_format($account['pending_replenishment'], 2);
            }

            if ($account['target_per_period'] !== null && $account['target_per_period'] > 0) {
                $period = $account['period'] ?? 'período';
                $text .= ", meta {$period}: $".number_format($account['target_per_period'], 2);
            }

            if ($account['goal_amount'] !== null && $account['goal_amount'] > 0) {
                $text .= ', meta total $'.number_format($account['goal_amount'], 2);
                if ($account['goal_progress_percent'] !== null) {
                    $text .= " ({$account['goal_progress_percent']}% cumplido)";
                }
            }

            $text .= "\n";

            foreach ($account['pending_withdrawals'] as $withdrawal) {
                $note = filled($withdrawal['notes']) ? " — {$withdrawal['notes']}" : '';
                $text .= "  - Retiro pendiente: $".number_format($withdrawal['pending'], 2)
                    ." del {$withdrawal['occurred_at']}{$note}\n";
            }
        }

        $text .= "\nUsa estos datos para responder preguntas como cuánto falta por reponer, saldo por cuenta o progreso hacia metas.\n";

        return $text;
    }

    /**
     * Resumen compacto para el asistente IA (menos tokens que getFourMonthSummary).
     */
    public function getCompactSummary(User $user): string
    {
        $metrics = app(FinancialMetricsService::class)->overviewFor($user);
        $savings = app(SavingsLedgerService::class)->summaryForUser($user);

        $lines = ['CONTEXTO FINANCIERO COMPACTO (solo datos de este usuario):'];

        if ($metrics['has_issued']) {
            $lines[] = '- Último presupuesto emitido: ingreso neto $'.number_format($metrics['net_income'], 2)
                .', saldo disponible $'.number_format($metrics['available_balance'], 2)
                .', pagado $'.number_format($metrics['paid_amount'], 2)
                .', cumplimiento '.$metrics['payment_compliance_percent'].'%';
            $lines[] = '- Ahorro planificado: $'.number_format($metrics['savings_amount'], 2)
                .' ('.$metrics['savings_percent'].'% del ingreso)';
            $lines[] = '- Gastos fijos: $'.number_format($metrics['fixed_expenses_amount'], 2)
                .' ('.$metrics['fixed_expenses_percent'].'% del ingreso)';
        } else {
            $lines[] = '- Sin presupuestos emitidos recientes.';
        }

        $lines[] = '- Presupuestos emitidos: '.$metrics['issued_plans_count']
            .', excedidos: '.$metrics['exceeded_plans_count'];
        $lines[] = '- Ingresos acumulados (emitidos): $'.number_format($metrics['total_income'], 2)
            .', gastos registrados: $'.number_format($metrics['total_expenses'], 2);

        $pending = (float) BudgetPlanItem::query()
            ->where('is_paid', false)
            ->where('amount', '>', 0)
            ->whereHas('budgetPlan', fn ($q) => $q->where('created_by', $user->id)->where('status', BudgetStatus::Issued))
            ->sum('amount');
        $lines[] = '- Pendiente por pagar en ítems emitidos: $'.number_format($pending, 2);

        $lines[] = '- Ahorros: saldo $'.number_format($savings['total_balance'], 2)
            .', por reponer $'.number_format($savings['pending_replenishment'], 2)
            .', cuentas activas: '.$savings['active_accounts'];

        $recent = BudgetPlan::query()
            ->where('created_by', $user->id)
            ->where('created_at', '>=', Carbon::now()->subMonths(4))
            ->orderByDesc('created_at')
            ->limit(3)
            ->get(['budget_number', 'title', 'net_income', 'remaining_balance', 'status']);

        if ($recent->isNotEmpty()) {
            $lines[] = '- Últimos presupuestos (sin detalle de ítems):';
            foreach ($recent as $plan) {
                $status = $plan->status->label();
                $lines[] = "  * {$plan->budget_number} «{$plan->title}» — ingreso $".number_format((float) $plan->net_income, 2)
                    .", disponible $".number_format((float) $plan->remaining_balance, 2)." ({$status})";
            }
        }

        $recentQuotes = Quote::query()
            ->where('created_by', $user->id)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get(['quote_number', 'recipient_name', 'total', 'status', 'currency']);

        if ($recentQuotes->isNotEmpty()) {
            $lines[] = '- Cotizaciones recientes:';
            foreach ($recentQuotes as $quote) {
                $status = $quote->status instanceof \BackedEnum ? $quote->status->label() : (string) $quote->status;
                $currency = $quote->currency instanceof \BackedEnum ? $quote->currency->value : (string) $quote->currency;
                $lines[] = "  * {$quote->quote_number} — {$quote->recipient_name} — {$currency} ".number_format((float) $quote->total, 2)." ({$status})";
            }
        } else {
            $lines[] = '- Sin cotizaciones recientes.';
        }

        return implode("\n", $lines);
    }
}
