<?php

namespace Tests\Unit;

use App\Filament\Widgets\Activity\ScopedActivityHeatmapWidget;
use App\Models\Quote;
use App\Models\User;
use App\Support\ActivityLogSilencer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_silencer_suppresses_model_logs_but_allows_manual_events(): void
    {
        $user = User::factory()->create();
        $quote = Quote::query()->create([
            'quote_number' => 'COT-TEST-0001',
            'issuer_name' => 'Emisor Test',
            'recipient_name' => 'Cliente Test',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $before = Activity::count();

        ActivityLogSilencer::withoutModelLogs(function () use ($quote): void {
            $quote->update(['footer_notes' => 'silenced change']);
        });

        $this->assertSame($before, Activity::count());

        activity()
            ->performedOn($quote)
            ->causedBy($user)
            ->event('cancelled')
            ->log('Cotización anulada');

        $this->assertSame($before + 1, Activity::count());
    }

    public function test_heatmap_normalizes_date_keys_to_y_m_d(): void
    {
        $user = User::factory()->create();

        Activity::query()->create([
            'log_name' => 'default',
            'description' => 'test',
            'subject_type' => Quote::class,
            'subject_id' => 1,
            'event' => 'created',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'created_at' => Carbon::parse('2026-06-27 15:00:00'),
            'updated_at' => Carbon::parse('2026-06-27 15:00:00'),
        ]);

        $this->actingAs($user);

        $data = app(ScopedActivityHeatmapWidget::class)->getData()['data'];
        $this->assertSame(1, $data['2026-06-27']);
    }
}
