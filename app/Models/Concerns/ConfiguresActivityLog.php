<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Support\LogOptions;

trait ConfiguresActivityLog
{
    protected static function baseActivityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    /**
     * @param  array<int, string>  $attributes
     */
    protected static function activityLogOptionsFor(array $attributes): LogOptions
    {
        return static::baseActivityLogOptions()->logOnly($attributes);
    }
}
