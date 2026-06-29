<?php

namespace App\Support;

use Spatie\Activitylog\Facades\Activity;

class ActivityLogSilencer
{
    public static function withoutModelLogs(callable $callback): mixed
    {
        Activity::disableLogging();

        try {
            return $callback();
        } finally {
            Activity::enableLogging();
        }
    }
}
