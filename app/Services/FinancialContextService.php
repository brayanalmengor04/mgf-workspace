<?php

namespace App\Services;

use App\Models\BudgetPlan;
use App\Models\User;
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
}
