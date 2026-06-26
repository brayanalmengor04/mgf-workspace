<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Quote $quote): bool
    {
        return $user->isAdmin() || $quote->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Quote $quote): bool
    {
        return $user->isAdmin() || $quote->created_by === $user->id;
    }

    public function delete(User $user, Quote $quote): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $quote->created_by === $user->id && $quote->isDraft();
    }
}
