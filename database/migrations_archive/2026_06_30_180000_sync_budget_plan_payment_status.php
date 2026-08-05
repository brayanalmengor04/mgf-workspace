<?php

use App\Models\BudgetPlan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        BudgetPlan::query()
            ->with('items')
            ->each(fn (BudgetPlan $plan) => $plan->syncPaymentStatus());
    }

    public function down(): void
    {
        // No reversible.
    }
};
