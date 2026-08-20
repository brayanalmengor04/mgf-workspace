<?php

namespace App\Policies;

use App\Models\SavingsAccount;
use App\Models\User;

class SavingsAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SavingsAccount $savingsAccount): bool
    {
        return $user->canViewGlobalData() || $savingsAccount->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SavingsAccount $savingsAccount): bool
    {
        return $user->canViewGlobalData() || $savingsAccount->user_id === $user->id;
    }

    public function delete(User $user, SavingsAccount $savingsAccount): bool
    {
        return $user->canViewGlobalData() || $savingsAccount->user_id === $user->id;
    }
}
