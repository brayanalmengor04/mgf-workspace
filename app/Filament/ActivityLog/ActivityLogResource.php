<?php

namespace App\Filament\ActivityLog;

use AlizHarb\ActivityLog\Resources\ActivityLogs\ActivityLogResource as BaseActivityLogResource;
use App\Support\ActivityLogScope;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends BaseActivityLogResource
{
    public static function getEloquentQuery(): Builder
    {
        return ActivityLogScope::apply(parent::getEloquentQuery());
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return ActivityLogScope::apply(parent::getGlobalSearchEloquentQuery());
    }

    public static function getNavigationBadge(): ?string
    {
        if (! config('filament-activity-log.resource.navigation_count_badge')) {
            return null;
        }

        return number_format(static::getEloquentQuery()->count());
    }
}
