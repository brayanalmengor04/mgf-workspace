<?php

namespace App\Support;

use App\Enums\SavingsProjectionCadence;
use App\Models\User;

class SavingsProjectionCadenceSelection
{
    public static function sessionKey(?User $user = null): string
    {
        $userId = ($user ?? auth()->user())?->id ?? 'guest';

        return 'savings_projection_cadence_'.$userId;
    }

    public static function get(?User $user = null): SavingsProjectionCadence
    {
        $stored = session(self::sessionKey($user));

        if (is_string($stored)) {
            return SavingsProjectionCadence::tryFrom($stored) ?? SavingsProjectionCadence::Biweekly;
        }

        return SavingsProjectionCadence::Biweekly;
    }

    public static function set(SavingsProjectionCadence|string $cadence, ?User $user = null): void
    {
        if (is_string($cadence)) {
            $cadence = SavingsProjectionCadence::tryFrom($cadence) ?? SavingsProjectionCadence::Biweekly;
        }

        session([self::sessionKey($user) => $cadence->value]);
    }
}
