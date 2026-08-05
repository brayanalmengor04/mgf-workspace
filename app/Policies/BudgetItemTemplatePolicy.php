<?php

namespace App\Policies;

use App\Models\BudgetItemTemplate;
use App\Models\User;

class BudgetItemTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BudgetItemTemplate $budgetItemTemplate): bool
    {
        return $user->canViewGlobalData() || $budgetItemTemplate->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, BudgetItemTemplate $budgetItemTemplate): bool
    {
        return $user->canViewGlobalData() || $budgetItemTemplate->user_id === $user->id;
    }

    public function delete(User $user, BudgetItemTemplate $budgetItemTemplate): bool
    {
        return $user->canViewGlobalData() || $budgetItemTemplate->user_id === $user->id;
    }
}
