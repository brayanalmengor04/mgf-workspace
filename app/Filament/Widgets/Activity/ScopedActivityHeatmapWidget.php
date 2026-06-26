<?php

namespace App\Filament\Widgets\Activity;

use AlizHarb\ActivityLog\Widgets\ActivityHeatmapWidget as BaseActivityHeatmapWidget;
use App\Support\ActivityLogScope;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class ScopedActivityHeatmapWidget extends BaseActivityHeatmapWidget
{
    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $driver = DB::getDriverName();

        $dateExpression = match ($driver) {
            'oracle' => 'TRUNC(created_at)',
            default => 'DATE(created_at)',
        };

        $data = ActivityLogScope::apply(Activity::query())
            ->select(
                DB::raw("$dateExpression as date"),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays($this->days))
            ->groupBy(DB::raw($dateExpression))
            ->get()
            ->pluck('count', 'date');

        return [
            'data' => $data,
            'max' => $data->max() ?: 1,
        ];
    }
}
