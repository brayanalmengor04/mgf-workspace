<?php

namespace App\Filament\Widgets\Activity;

use AlizHarb\ActivityLog\Widgets\ActivityHeatmapWidget as BaseActivityHeatmapWidget;
use App\Support\ActivityLogScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class ScopedActivityHeatmapWidget extends BaseActivityHeatmapWidget
{
    protected string $view = 'filament.widgets.activity-heatmap';

    protected int | string | array $columnSpan = 'full';

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
            ->mapWithKeys(fn ($row) => [
                Carbon::parse($row->date)->toDateString() => (int) $row->count,
            ]);

        return [
            'data' => $data->all(),
            'max' => (int) ($data->max() ?: 1),
        ];
    }
}
