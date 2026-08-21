<?php

namespace App\Support;

use App\Models\SavingsAccount;
use App\Models\User;

class SavingsAccountSelection
{
    public static function sessionKey(?User $user = null): string
    {
        $userId = ($user ?? auth()->user())?->id ?? 'guest';

        return 'savings_dashboard_account_'.$userId;
    }

    public static function id(?User $user = null): ?int
    {
        $stored = session(self::sessionKey($user));

        if (is_numeric($stored)) {
            return (int) $stored;
        }

        return self::defaultId($user);
    }

    public static function set(?int $accountId, ?User $user = null): void
    {
        session([self::sessionKey($user) => $accountId]);
    }

    public static function defaultId(?User $user = null): ?int
    {
        $user = $user ?? auth()->user();

        if ($user === null) {
            return null;
        }

        return SavingsAccount::query()
            ->forUser($user)
            ->active()
            ->orderBy('name')
            ->value('id');
    }
}
