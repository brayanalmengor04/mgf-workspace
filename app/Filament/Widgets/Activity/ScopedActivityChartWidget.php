<?php

namespace App\Filament\Widgets\Activity;

use AlizHarb\ActivityLog\Widgets\ActivityChartWidget as BaseActivityChartWidget;
use App\Support\ActivityLogScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class ScopedActivityChartWidget extends BaseActivityChartWidget
{
    protected function getData(): array
    {
        $days = config('filament-activity-log.widgets.activity_chart.days', 30);
        $fillColor = config('filament-activity-log.widgets.activity_chart.fill_color', 'rgba(16, 185, 129, 0.1)');
        $borderColor = config('filament-activity-log.widgets.activity_chart.border_color', '#10b981');

        $driver = DB::getDriverName();

        $dateExpression = match ($driver) {
            'oracle' => 'TRUNC(created_at)',
            default => 'DATE(created_at)',
        };

        $data = ActivityLogScope::apply(Activity::query())
            ->select(
                DB::raw("$dateExpression as activity_date"),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(DB::raw($dateExpression))
            ->orderBy(DB::raw($dateExpression))
            ->get()
            ->pluck('count', 'activity_date');

        return [
            'datasets' => [
                [
                    'label' => config('filament-activity-log.widgets.activity_chart.label', __('filament-activity-log::activity.widgets.activity_chart.label')),
                    'data' => $data->values()->toArray(),
                    'borderColor' => $borderColor,
                    'backgroundColor' => $fillColor,
                    'fill' => config('filament-activity-log.widgets.activity_chart.fill', true),
                    'tension' => config('filament-activity-log.widgets.activity_chart.tension', 0.3),
                ],
            ],
            'labels' => $data->keys()->map(fn ($date) => Carbon::parse($date)->format(
                config('filament-activity-log.widgets.activity_chart.date_format', 'M d')
            ))->toArray(),
        ];
    }
}
