<?php

namespace App\Filament\Resources\BudgetPlans\Pages;

use App\Enums\BudgetCategoryType;
use App\Enums\BudgetStatus;
use App\Filament\Concerns\InteractsWithEmbeddedWizard;
use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use App\Filament\Resources\BudgetPlans\Concerns\RecalculatesBudgetTotals;
use App\Filament\Resources\BudgetPlans\Concerns\SyncsBudgetPlanItems;
use App\Filament\Resources\BudgetPlans\Schemas\BudgetPlanForm;
use App\Filament\Support\DocumentShareActions;
use App\Models\BudgetPlan;
use App\Services\Budgets\BudgetItemTemplateSync;
use App\Services\Budgets\BudgetPdfService;
use App\Services\Budgets\BudgetPlanDuplicator;
use App\Services\Budgets\BudgetShareService;
use App\Support\ActivityLogSilencer;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Response;

class EditBudgetPlan extends EditRecord
{
    use InteractsWithEmbeddedWizard;
    use RecalculatesBudgetTotals;
    use SyncsBudgetPlanItems;

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
                    'id' => $item->id,
                    'concept' => $item->concept,
                    'notes' => $item->notes,
                    'amount' => (float) $item->amount,
                    'is_paid' => (bool) $item->is_paid,
                    'paid_at' => $item->paid_at?->format('Y-m-d'),
                    'savings_account_id' => $item->savings_account_id,
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

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('issue')
                    ->label('Generar PDF')
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
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
                    ->icon(Heroicon::OutlinedArrowPath)
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
                    ->icon(Heroicon::OutlinedArrowDownTray)
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
            ])
                ->label('PDF')
                ->icon(Heroicon::OutlinedDocumentText)
                ->button()
                ->color('gray'),

            ActionGroup::make([
                DocumentShareActions::email(
                    'sendEmail',
                    fn (BudgetPlan $record, string $email, $user) => app(BudgetShareService::class)->sendEmail($record, $email, $user),
                    prepareRecord: function (BudgetPlan $record): void {
                        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                        $this->syncItemsFromForm();
                        $this->recalculateBudgetTotals($record->refresh());
                        app(BudgetShareService::class)->preparePdfForShare($record->refresh());
                    },
                ),
                DocumentShareActions::whatsApp(
                    'sendWhatsApp',
                    fn (BudgetPlan $record, string $phone, $user) => app(BudgetShareService::class)->whatsAppLinks($record, $phone, $user),
                    prepareRecord: function (BudgetPlan $record): void {
                        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                        $this->syncItemsFromForm();
                        $this->recalculateBudgetTotals($record->refresh());
                        app(BudgetShareService::class)->preparePdfForShare($record->refresh());
                    },
                ),
            ])
                ->label('Enviar')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->button()
                ->color('primary'),

            ActionGroup::make([
                Action::make('save_to_catalog')
                    ->label('Guardar en catálogo')
                    ->icon(Heroicon::OutlinedBookmark)
                    ->requiresConfirmation()
                    ->modalDescription('Los conceptos de este presupuesto se guardarán en tus conceptos frecuentes.')
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
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
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
                    ->icon(Heroicon::OutlinedArchiveBox)
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
            ])
                ->label('Más')
                ->icon(Heroicon::OutlinedEllipsisHorizontal)
                ->button()
                ->color('gray')
                ->dropdownPlacement('bottom-end'),
        ];
    }
}
