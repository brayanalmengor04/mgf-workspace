<?php

namespace App\Filament\Widgets\Activity;

use AlizHarb\ActivityLog\Widgets\LatestActivityWidget as BaseLatestActivityWidget;
use App\Support\ActivityLogScope;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ScopedLatestActivityWidget extends BaseLatestActivityWidget
{
    public function table(Table $table): Table
    {
        return parent::table($table)
            ->query(
                ActivityLogScope::apply(
                    Activity::query()
                        ->with(['causer', 'subject'])
                        ->latest()
                )->limit(config('filament-activity-log.widgets.latest_activity.limit', 10))
            );
    }
}
