<?php

namespace App\Filament\Widgets;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Filament\Widgets\Widget;

class QuoteListStatsWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.quote-list-stats';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, int>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return ['drafts' => 0, 'sent' => 0, 'accepted' => 0];
        }

        $query = Quote::query()->forUser($user);

        return [
            'drafts' => (clone $query)->where('status', QuoteStatus::Draft)->count(),
            'issued' => (clone $query)->where('status', QuoteStatus::Issued)->count(),
            'cancelled' => (clone $query)->where('status', QuoteStatus::Cancelled)->count(),
        ];
    }
}
