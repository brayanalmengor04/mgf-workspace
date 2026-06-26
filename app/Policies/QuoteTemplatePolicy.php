<?php

namespace App\Policies;

use App\Models\QuoteTemplate;
use App\Models\User;

class QuoteTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, QuoteTemplate $quoteTemplate): bool
    {
        return $user->isAdmin() || $quoteTemplate->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, QuoteTemplate $quoteTemplate): bool
    {
        return $user->isAdmin() || $quoteTemplate->user_id === $user->id;
    }

    public function delete(User $user, QuoteTemplate $quoteTemplate): bool
    {
        return $user->isAdmin() || $quoteTemplate->user_id === $user->id;
    }
}
