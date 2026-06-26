<?php

namespace App\Authorizers;

use App\Models\User;

class ActivityLogAuthorizer
{
    public function __invoke(User $user): bool
    {
        return $user->is_active;
    }
}
