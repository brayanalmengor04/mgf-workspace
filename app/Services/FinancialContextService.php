<?php

namespace App\Services;

use App\Models\BudgetPlan;
use App\Models\User;
use Carbon\Carbon;

class FinancialContextService
{
    /**
     * Generates a 4-month summary of the user's budget plans.
     */
    public function getFourMonthSummary(User $user): string
    {
        $startDate = Carbon::now()->subMonths(4);

        $budgets = BudgetPlan::forUser($user)
            ->with('items')
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
}
