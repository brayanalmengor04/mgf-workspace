<?php

namespace App\Filament\Resources\BudgetPlans\Pages;

use App\Enums\BudgetCategoryType;
use App\Enums\BudgetStatus;
use App\Filament\Concerns\InteractsWithEmbeddedWizard;
use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use App\Filament\Resources\BudgetPlans\Concerns\RecalculatesBudgetTotals;
use App\Filament\Resources\BudgetPlans\Schemas\BudgetPlanForm;
use App\Models\BudgetPlan;
use App\Services\Budgets\BudgetItemTemplateSync;
use App\Services\Budgets\BudgetPdfService;
use App\Services\Budgets\BudgetPlanDuplicator;
use App\Support\ActivityLogSilencer;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Response;

class EditBudgetPlan extends EditRecord
{
    use InteractsWithEmbeddedWizard;
    use RecalculatesBudgetTotals;

    protected static string $resource = BudgetPlanResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        $record->load('items');

        foreach (BudgetCategoryType::cases() as $category) {
            $data["items_{$category->value}"] = $record->items
                ->filter(fn ($item) => $item->category_type === $category)
                ->values()
                ->map(fn ($item) => [
                    'concept' => $item->concept,
                    'notes' => $item->notes,
                    'amount' => (float) $item->amount,
                    'is_paid' => (bool) $item->is_paid,
                    'paid_at' => $item->paid_at?->format('Y-m-d'),
                    'category_type' => $category->value,
                ])
                ->all();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->syncItemsFromForm();
        $this->recalculateBudgetTotals($this->record->refresh());
    }

    protected function syncItemsFromForm(): void
    {
        /** @var BudgetPlan $record */
        $record = $this->record;
        $state = $this->form->getRawState();
        $items = BudgetPlanForm::collectItemsFromState($state);

        $record->items()->delete();

        foreach ($items as $index => $item) {
            $record->items()->create([
                'category_type' => $item['category_type'],
                'concept' => $item['concept'],
                'notes' => $item['notes'],
                'amount' => $item['amount'],
                'is_paid' => $item['is_paid'],
                'paid_at' => $item['paid_at'] ?? null,
                'sort_order' => $index,
            ]);
        }

        $record->syncPaymentStatus();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('issue')
                ->label('Generar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Se calcularán los porcentajes y se generará el PDF del presupuesto.')
                ->visible(fn (BudgetPlan $record): bool => $record->isDraft())
                ->action(function (BudgetPlan $record): void {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    $this->syncItemsFromForm();
                    $this->recalculateBudgetTotals($record->refresh());
                    app(BudgetPdfService::class)->issue($record->refresh());

                    Notification::make()
                        ->title('Presupuesto generado')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'total_allocated',
                        'remaining_balance',
                    ]);
                }),
            Action::make('regenerate')
                ->label('Regenerar PDF')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->visible(fn (BudgetPlan $record): bool => $record->isIssued())
                ->action(function (BudgetPlan $record): void {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    $this->syncItemsFromForm();
                    $this->recalculateBudgetTotals($record->refresh());
                    app(BudgetPdfService::class)->regenerate($record->refresh());

                    Notification::make()
                        ->title('PDF regenerado')
                        ->success()
                        ->send();
                }),
            Action::make('download')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (BudgetPlan $record): bool => $record->pdf_path !== null)
                ->action(function (BudgetPlan $record) {
                    $path = app(BudgetPdfService::class)->downloadPath($record);

                    if ($path === null) {
                        Notification::make()
                            ->title('PDF no disponible')
                            ->danger()
                            ->send();

                        return;
                    }

                    return Response::download($path, "{$record->budget_number}.pdf");
                }),
            Action::make('save_to_catalog')
                ->label('Guardar en catálogo')
                ->icon('heroicon-o-bookmark')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Los conceptos de este presupuesto se guardarán o actualizarán en tus conceptos frecuentes.')
                ->action(function (BudgetPlan $record): void {
                    $user = auth()->user();

                    if ($user === null) {
                        return;
                    }

                    $synced = app(BudgetItemTemplateSync::class)->syncFromPlan($record, $user);

                    Notification::make()
                        ->title('Catálogo actualizado')
                        ->body("Se guardaron {$synced} concepto(s) frecuentes.")
                        ->success()
                        ->send();
                }),
            Action::make('duplicate')
                ->label('Duplicar')
                ->icon('heroicon-o-document-duplicate')
                ->action(function (BudgetPlan $record): void {
                    $duplicate = app(BudgetPlanDuplicator::class)->duplicate($record);

                    Notification::make()
                        ->title('Presupuesto duplicado')
                        ->success()
                        ->send();

                    $this->redirect(BudgetPlanResource::getUrl('edit', ['record' => $duplicate]));
                }),
            Action::make('archive')
                ->label('Archivar')
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (BudgetPlan $record): bool => $record->status !== BudgetStatus::Archived)
                ->action(function (BudgetPlan $record): void {
                    ActivityLogSilencer::withoutModelLogs(function () use ($record): void {
                        $record->update(['status' => BudgetStatus::Archived]);
                    });

                    activity()
                        ->performedOn($record)
                        ->causedBy(auth()->user())
                        ->event('archived')
                        ->log('Presupuesto archivado');

                    Notification::make()
                        ->title('Presupuesto archivado')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
