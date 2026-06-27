<?php

namespace App\Filament\Resources\Quotes\Schemas;

use App\Enums\QuoteCurrency;
use App\Filament\Resources\Concerns\HasPartyFields;
use App\Models\Quote;
use App\Models\QuoteTemplate;
use App\Support\MoneyFormatter;
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class QuoteForm
{
    use HasPartyFields;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Wizard::make([
                    Step::make('Inicio')
                        ->description('Plantilla base y moneda')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->schema([
                            Select::make('quote_template_id')
                                ->label('Plantilla')
                                ->options(fn (): array => QuoteTemplate::query()
                                    ->forUser(auth()->user())
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (?string $state, callable $set): void {
                                    $set('override_template_fields', false);

                                    if ($state === null) {
                                        return;
                                    }

                                    $template = QuoteTemplate::query()
                                        ->forUser(auth()->user())
                                        ->find($state);

                                    if ($template === null) {
                                        return;
                                    }

                                    $set('issuer_name', $template->issuer_name);
                                    $set('issuer_ruc', $template->issuer_ruc);
                                    $set('issuer_dv', $template->issuer_dv);
                                    $set('issuer_has_dv', $template->issuer_has_dv);
                                    $set('issuer_address', $template->issuer_address);
                                    $set('issuer_phone', $template->issuer_phone);
                                    $set('issuer_email', $template->issuer_email);
                                    $set('bank_name', $template->bank_name);
                                    $set('bank_account_number', $template->bank_account_number);
                                    $set('yappy_id', $template->yappy_id);
                                    $set('footer_notes', $template->footer_notes);
                                    $set('currency', $template->currency?->value ?? QuoteCurrency::Pab->value);
                                }),
                            Toggle::make('override_template_fields')
                                ->label('Personalizar datos de la plantilla')
                                ->helperText('Emisor, moneda y datos de pago se heredan de la plantilla. Activa esto para editarlos en esta cotización.')
                                ->default(false)
                                ->live()
                                ->dehydrated(false)
                                ->visible(fn (Get $get, string $operation): bool => $operation === 'create' && filled($get('quote_template_id')))
                                ->columnSpanFull(),
                            Select::make('currency')
                                ->label('Moneda')
                                ->options(QuoteCurrency::options())
                                ->default(QuoteCurrency::Pab->value)
                                ->required()
                                ->native(false)
                                ->live()
                                ->disabled(fn (Get $get, string $operation): bool => static::isTemplateDataLocked($get, $operation))
                                ->dehydrated(true),
                            TextInput::make('quote_number')
                                ->label('Número')
                                ->disabled()
                                ->dehydrated(false)
                                ->visibleOn('edit'),
                        ])
                        ->columns(2),
                    Step::make('Emisor')
                        ->description('Datos de quien emite la cotización')
                        ->icon(Heroicon::OutlinedBuildingOffice)
                        ->schema(static::partyFields('issuer', 'Nombre empresa / persona', lockWhenFromTemplate: true))
                        ->columns(2),
                    Step::make('Cliente')
                        ->description('Datos del destinatario')
                        ->icon(Heroicon::OutlinedUser)
                        ->schema(static::partyFields('recipient', 'Nombre empresa / persona'))
                        ->columns(2),
                    Step::make('Detalle')
                        ->description('Líneas, precios e ITBMS')
                        ->icon(Heroicon::OutlinedListBullet)
                        ->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->cloneable()
                                ->collapsible()
                                ->collapsed()
                                ->itemLabel(function (array $state): ?string {
                                    if (blank($state['description'] ?? null)) {
                                        $qty = (float) ($state['quantity'] ?? 1);
                                        $price = (float) ($state['unit_price'] ?? 0);

                                        return $qty > 0 || $price > 0
                                            ? 'Item sin descripción'
                                            : 'Nuevo item';
                                    }

                                    return Str::limit((string) $state['description'], 50);
                                })
                                ->schema([
                                    TextInput::make('quantity')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->default(1)
                                        ->required()
                                        ->dehydrateStateUsing(fn (?string $state): float => filled($state) ? (float) $state : 1.0)
                                        ->live(onBlur: true)
                                        ->columnSpan(1),
                                    TextInput::make('description')
                                        ->label('Descripción')
                                        ->required()
                                        ->columnSpan(3),
                                    TextInput::make('unit_price')
                                        ->label('Precio unitario')
                                        ->numeric()
                                        ->prefix(fn (Get $get): string => QuoteCurrency::resolve($get('../../currency'))->symbol())
                                        ->default(0)
                                        ->required()
                                        ->dehydrateStateUsing(fn (?string $state): float => filled($state) ? (float) $state : 0.0)
                                        ->live(onBlur: true)
                                        ->columnSpan(2),
                                    TextInput::make('tax_rate')
                                        ->label('ITBMS %')
                                        ->numeric()
                                        ->default(7)
                                        ->dehydrateStateUsing(fn (?string $state): float => filled($state) ? (float) $state : 7.0)
                                        ->suffix('%')
                                        ->live(onBlur: true)
                                        ->columnSpan(1),
                                    Placeholder::make('line_preview')
                                        ->label('Total línea')
                                        ->content(function (Get $get): string {
                                            $qty = (float) ($get('quantity') ?? 0);
                                            $price = (float) ($get('unit_price') ?? 0);
                                            $taxRate = (float) ($get('tax_rate') ?? 0);
                                            $subtotal = $qty * $price;
                                            $total = $subtotal + ($subtotal * ($taxRate / 100));

                                            return MoneyFormatter::format($total, $get('../../currency'));
                                        })
                                        ->columnSpan(2),
                                ])
                                ->columns(9)
                                ->defaultItems(1)
                                ->reorderable()
                                ->addActionLabel('Agregar item')
                                ->columnSpanFull(),
                            Placeholder::make('subtotal_display')
                                ->label('Subtotal')
                                ->content(fn (?Quote $record): string => MoneyFormatter::format((float) ($record?->subtotal ?? 0), $record?->currency))
                                ->visibleOn('edit'),
                            Placeholder::make('tax_display')
                                ->label('ITBMS')
                                ->content(fn (?Quote $record): string => MoneyFormatter::format((float) ($record?->tax_amount ?? 0), $record?->currency))
                                ->visibleOn('edit'),
                            Placeholder::make('total_display')
                                ->label('Total')
                                ->content(fn (?Quote $record): string => MoneyFormatter::format((float) ($record?->total ?? 0), $record?->currency))
                                ->visibleOn('edit'),
                        ]),
                    Step::make('Cierre')
                        ->description('Banco, Yappy y notas al pie')
                        ->icon(Heroicon::OutlinedBanknotes)
                        ->schema(array_merge(static::footerFields(lockWhenFromTemplate: true), [
                            Actions::make([
                                Action::make('preview')
                                    ->label('Ver Vista Previa de Cotización')
                                    ->icon(Heroicon::OutlinedEye)
                                    ->color('gray')
                                    ->modalHeading('Vista Previa de Cotización')
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Cerrar')
                                    ->modalWidth('4xl')
                                    ->modalContent(function (Get $get) {
                                        $items = $get('items') ?? [];
                                        $subtotal = 0;
                                        $taxAmount = 0;
                                        
                                        foreach ($items as $item) {
                                            $qty = (float) ($item['quantity'] ?? 0);
                                            $price = (float) ($item['unit_price'] ?? 0);
                                            $taxRate = (float) ($item['tax_rate'] ?? 0);
                                            $lineSubtotal = $qty * $price;
                                            $lineTax = $lineSubtotal * ($taxRate / 100);
                                            
                                            $subtotal += $lineSubtotal;
                                            $taxAmount += $lineTax;
                                        }
                                        
                                        $total = $subtotal + $taxAmount;

                                        $data = [
                                            'quote_number' => $get('quote_number'),
                                            'currency' => $get('currency'),
                                            'issuer_name' => $get('issuer_name'),
                                            'issuer_ruc' => $get('issuer_ruc'),
                                            'issuer_has_dv' => $get('issuer_has_dv'),
                                            'issuer_dv' => $get('issuer_dv'),
                                            'issuer_address' => $get('issuer_address'),
                                            'issuer_phone' => $get('issuer_phone'),
                                            'issuer_email' => $get('issuer_email'),
                                            'recipient_name' => $get('recipient_name'),
                                            'recipient_ruc' => $get('recipient_ruc'),
                                            'recipient_has_dv' => $get('recipient_has_dv'),
                                            'recipient_dv' => $get('recipient_dv'),
                                            'recipient_address' => $get('recipient_address'),
                                            'recipient_phone' => $get('recipient_phone'),
                                            'recipient_email' => $get('recipient_email'),
                                            'bank_name' => $get('bank_name'),
                                            'bank_account_number' => $get('bank_account_number'),
                                            'yappy_id' => $get('yappy_id'),
                                            'footer_notes' => $get('footer_notes'),
                                            'items' => $items,
                                            'totals' => [
                                                'subtotal' => $subtotal,
                                                'tax_amount' => $taxAmount,
                                                'total' => $total,
                                            ],
                                        ];

                                        return view('quotes.preview-modal', ['data' => $data]);
                                    }),
                            ])->columnSpanFull(),
                        ]))
                        ->columns(2),
                ])
                    ->label('Cotización')
                    ->columnSpanFull()
                    ->contained()
                    ->skippable(false),
            ]);
    }
}
