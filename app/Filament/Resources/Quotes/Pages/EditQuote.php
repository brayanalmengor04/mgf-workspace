<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Enums\QuoteStatus;
use App\Filament\Concerns\InteractsWithEmbeddedWizard;
use App\Filament\Resources\Quotes\Concerns\RecalculatesQuoteTotals;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Support\DocumentShareActions;
use App\Models\Quote;
use App\Support\ActivityLogSilencer;
use App\Services\Quotes\QuotePdfService;
use App\Services\Quotes\QuoteShareService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Response;

class EditQuote extends EditRecord
{
    use InteractsWithEmbeddedWizard;
    use RecalculatesQuoteTotals;

    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('issue')
                    ->label('Emitir PDF')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->requiresConfirmation()
                    ->visible(fn (Quote $record): bool => $record->isDraft())
                    ->action(function (Quote $record): void {
                        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                        app(QuotePdfService::class)->issue($record->refresh());

                        Notification::make()
                            ->title('Cotización emitida')
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'subtotal',
                            'tax_amount',
                            'total',
                        ]);
                    }),
                Action::make('regenerate')
                    ->label('Regenerar PDF')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->requiresConfirmation()
                    ->visible(fn (Quote $record): bool => $record->isIssued())
                    ->action(function (Quote $record): void {
                        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                        app(QuotePdfService::class)->regenerate($record->refresh());

                        Notification::make()
                            ->title('PDF regenerado')
                            ->success()
                            ->send();
                    }),
                Action::make('download')
                    ->label('Descargar PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn (Quote $record): bool => $record->pdf_path !== null)
                    ->action(function (Quote $record) {
                        $path = app(QuotePdfService::class)->downloadPath($record);

                        if ($path === null) {
                            Notification::make()
                                ->title('PDF no disponible')
                                ->danger()
                                ->send();

                            return;
                        }

                        return Response::download($path, "{$record->quote_number}.pdf");
                    }),
            ])
                ->label('PDF')
                ->icon(Heroicon::OutlinedDocumentText)
                ->button()
                ->color('gray'),

            ActionGroup::make([
                DocumentShareActions::email(
                    'sendEmail',
                    fn (Quote $record, string $email, $user) => app(QuoteShareService::class)->sendEmail($record, $email, $user),
                    prepareRecord: function (Quote $record): void {
                        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                        $this->recalculateQuoteTotals($record->refresh());
                        app(QuoteShareService::class)->preparePdfForShare($record->refresh());
                    },
                ),
                DocumentShareActions::whatsApp(
                    'sendWhatsApp',
                    fn (Quote $record, string $phone, $user) => app(QuoteShareService::class)->whatsAppLinks($record, $phone, $user),
                    prepareRecord: function (Quote $record): void {
                        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                        $this->recalculateQuoteTotals($record->refresh());
                        app(QuoteShareService::class)->preparePdfForShare($record->refresh());
                    },
                ),
            ])
                ->label('Enviar')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->button()
                ->color('primary'),

            ActionGroup::make([
                Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->action(function (Quote $record): void {
                        $duplicate = $record->replicate([
                            'quote_number',
                            'status',
                            'generated_payload',
                            'pdf_path',
                            'issued_at',
                        ]);

                        $duplicate->quote_number = app(\App\Services\Quotes\QuoteNumberGenerator::class)->generate();
                        $duplicate->status = QuoteStatus::Draft;
                        $duplicate->created_by = auth()->id();
                        $duplicate->save();

                        foreach ($record->items as $item) {
                            $duplicate->items()->create($item->only([
                                'sort_order',
                                'quantity',
                                'description',
                                'unit_price',
                                'tax_rate',
                                'tax_amount',
                                'line_total',
                            ]));
                        }

                        $this->recalculateQuoteTotals($duplicate);

                        activity()
                            ->performedOn($duplicate)
                            ->causedBy(auth()->user())
                            ->event('duplicated')
                            ->withProperties(['source_quote' => $record->quote_number])
                            ->log('Cotización duplicada');

                        Notification::make()
                            ->title('Cotización duplicada')
                            ->success()
                            ->send();

                        $this->redirect(QuoteResource::getUrl('edit', ['record' => $duplicate]));
                    }),
                Action::make('cancel')
                    ->label('Anular')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Quote $record): bool => $record->status !== QuoteStatus::Cancelled)
                    ->action(function (Quote $record): void {
                        ActivityLogSilencer::withoutModelLogs(function () use ($record): void {
                            $record->update(['status' => QuoteStatus::Cancelled]);
                        });

                        activity()
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->event('cancelled')
                            ->log('Cotización anulada');

                        Notification::make()
                            ->title('Cotización anulada')
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

    protected function afterSave(): void
    {
        $this->recalculateQuoteTotals($this->record);
    }
}
