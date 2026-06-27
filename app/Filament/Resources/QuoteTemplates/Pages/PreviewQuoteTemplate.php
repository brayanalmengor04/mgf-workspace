<?php

namespace App\Filament\Resources\QuoteTemplates\Pages;

use App\Enums\QuotePdfLayout;
use App\Filament\Resources\QuoteTemplates\QuoteTemplateResource;
use App\Support\QuotePreviewData;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class PreviewQuoteTemplate extends Page
{
    use InteractsWithRecord;

    protected static string $resource = QuoteTemplateResource::class;

    protected string $view = 'filament.quote-templates.preview';

    protected static ?string $title = 'Vista previa del estilo PDF';

    protected static bool $shouldRegisterNavigation = false;

    protected Width | string | null $maxContentWidth = Width::Full;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->mountCanAuthorizeAccess();
    }

    public function getDocumentHtml(): string
    {
        $template = $this->getRecord();

        $layout = QuotePdfLayout::tryFrom((string) $template->pdf_layout)
            ?? QuotePdfLayout::Classic;

        return QuotePreviewData::renderLayoutDocument(
            $layout,
            $template->currency?->value,
            $template->primary_color,
            $template->logo_path,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Volver a editar')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->url(fn (): string => QuoteTemplateResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }
}
