<?php

namespace App\Console\Commands;

use App\Models\BudgetPlan;
use Illuminate\Console\Command;

class BudgetSyncPaymentStatusCommand extends Command
{
    protected $signature = 'budget:sync-payment-status';

    protected $description = 'Sincroniza is_paid de presupuestos según el estado de sus líneas';

    public function handle(): int
    {
        $count = 0;

        BudgetPlan::query()
            ->with('items')
            ->each(function (BudgetPlan $plan) use (&$count): void {
                $before = $plan->is_paid;
                $plan->syncPaymentStatus();
                $plan->refresh();

                if ($before !== $plan->is_paid) {
                    $count++;
                }
            });

        $this->info("Presupuestos actualizados: {$count}");

        return self::SUCCESS;
    }
}
