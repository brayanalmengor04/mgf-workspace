<?php

namespace App\Policies;

use App\Models\BudgetPlan;
use App\Models\User;

class BudgetPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BudgetPlan $budgetPlan): bool
    {
        return $user->canViewGlobalData() || $budgetPlan->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, BudgetPlan $budgetPlan): bool
    {
        return $user->canViewGlobalData() || $budgetPlan->created_by === $user->id;
    }

    public function delete(User $user, BudgetPlan $budgetPlan): bool
    {
        if ($user->canViewGlobalData()) {
            return true;
        }

        return $budgetPlan->created_by === $user->id && $budgetPlan->isDraft();
    }
}
