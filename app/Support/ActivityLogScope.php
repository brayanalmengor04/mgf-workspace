<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityLogScope
{
    /**
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    public static function apply(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        if ($user === null || $user->isAdmin()) {
            return $query;
        }

        return $query
            ->where('causer_type', $user->getMorphClass())
            ->where('causer_id', $user->id);
    }

    /**
     * @return Builder<Activity>
     */
    public static function query(): Builder
    {
        return static::apply(Activity::query());
    }
}
