<?php

namespace App\Filament\Resources\BudgetPlans\Concerns;

use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use App\Services\Budgets\BudgetScanPipeline;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait ImportsBudgetFromScan
{
    protected function importBudgetFromPhotoAction(): Action
    {
        $maxMb = (int) config('services.budget_scan.max_mb', 8);

        return Action::make('importFromPhoto')
            ->label('Crear desde foto (IA)')
            ->icon(Heroicon::OutlinedCamera)
            ->color('info')
            ->modalHeading('Crea tu presupuesto desde una foto')
            ->modalDescription('Nuestro asistente de IA analiza una imagen de tu presupuesto a mano y te propone un borrador editable. Revisarás los datos antes de guardar.')
            ->modalSubmitActionLabel('Analizar con IA')
            ->form([
                FileUpload::make('scan_image')
                    ->label('Imagen del presupuesto')
                    ->image()
                    ->required()
                    ->maxSize($maxMb * 1024)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->directory('temp/budget-scans')
                    ->visibility('private')
                    ->helperText("Máximo {$maxMb} MB. JPEG, PNG o WebP."),
            ])
            ->action(function (array $data): void {
                $user = auth()->user();
                if ($user === null) {
                    return;
                }

                $path = $data['scan_image'] ?? null;
                if ($path === null) {
                    Notification::make()->title('Selecciona una imagen')->danger()->send();

                    return;
                }

                try {
                    $absolutePath = $this->resolveScanImagePath($path);
                    app(BudgetScanPipeline::class)->processAbsolutePath($absolutePath, $user);

                    $this->redirect(BudgetPlanResource::getUrl('review-scan'), navigate: true);
                } catch (\Throwable $exception) {
                    Notification::make()
                        ->title('No se pudo analizar la imagen')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected function resolveScanImagePath(string|TemporaryUploadedFile $path): string
    {
        if ($path instanceof TemporaryUploadedFile) {
            return $path->getRealPath();
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->path($path);
            }
        }

        throw new \RuntimeException('No se encontró el archivo de imagen subido.');
    }
}
