<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->isStaff()) {
            return false;
        }

        return $user->id !== $model->id;
    }
}
