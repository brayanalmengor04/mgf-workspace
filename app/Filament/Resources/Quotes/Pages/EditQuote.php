<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\Concerns\RecalculatesQuoteTotals;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use App\Services\Quotes\QuotePdfService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Response;

class EditQuote extends EditRecord
{
    use RecalculatesQuoteTotals;

    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('issue')
                ->label('Emitir PDF')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
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
                ->icon('heroicon-o-arrow-path')
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
                ->icon('heroicon-o-arrow-down-tray')
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
            Action::make('duplicate')
                ->label('Duplicar')
                ->icon('heroicon-o-document-duplicate')
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
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (Quote $record): bool => $record->status !== QuoteStatus::Cancelled)
                ->action(function (Quote $record): void {
                    $record->update(['status' => QuoteStatus::Cancelled]);

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
        ];
    }

    protected function afterSave(): void
    {
        $this->recalculateQuoteTotals($this->record);
    }
}
