<?php

namespace App\Livewire;

use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use App\Services\Budgets\BudgetScanPipeline;
use Filament\Notifications\Notification;
use Livewire\Component;
use Livewire\WithFileUploads;

class BudgetScanWidget extends Component
{
    use WithFileUploads;

    public $scanImage;

    public bool $isProcessing = false;

    public ?string $errorMessage = null;

    public function processScan(): void
    {
        $maxMb = (int) config('services.budget_scan.max_mb', 8);

        $this->validate([
            'scanImage' => 'required|image|mimes:jpeg,jpg,png,webp|max:'.($maxMb * 1024),
        ]);

        $user = auth()->user();
        if ($user === null) {
            return;
        }

        $this->isProcessing = true;
        $this->errorMessage = null;

        try {
            app(BudgetScanPipeline::class)->processUploadedFile($this->scanImage, $user);
            $this->redirect(BudgetPlanResource::getUrl('review-scan'), navigate: true);
        } catch (\Throwable $exception) {
            $this->errorMessage = $exception->getMessage();
            Notification::make()
                ->title('Error al escanear')
                ->body($this->errorMessage)
                ->danger()
                ->send();
        } finally {
            $this->isProcessing = false;
            $this->reset('scanImage');
        }
    }

    public function render()
    {
        return view('livewire.budget-scan-widget');
    }
}
