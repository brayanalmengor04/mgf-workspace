<?php

namespace App\Filament\Resources\Quotes\Schemas;

use App\Enums\QuoteCurrency;
use App\Filament\Resources\Concerns\HasPartyFields;
use App\Models\Quote;
use App\Models\QuoteTemplate;
use App\Support\MoneyFormatter;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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
                            Select::make('currency')
                                ->label('Moneda')
                                ->options(QuoteCurrency::options())
                                ->default(QuoteCurrency::Pab->value)
                                ->required()
                                ->native(false)
                                ->live(),
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
                        ->schema(static::partyFields('issuer', 'Nombre empresa / persona'))
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
                                ->collapsible()
                                ->cloneable()
                                ->schema([
                                    TextInput::make('quantity')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->default(1)
                                        ->required()
                                        ->dehydrateStateUsing(fn (?string $state): float => filled($state) ? (float) $state : 1.0)
                                        ->live(onBlur: true),
                                    TextInput::make('description')
                                        ->label('Descripción')
                                        ->required()
                                        ->columnSpan(2),
                                    TextInput::make('unit_price')
                                        ->label('Precio unitario')
                                        ->numeric()
                                        ->prefix(fn (Get $get): string => QuoteCurrency::resolve($get('../../currency'))->symbol())
                                        ->default(0)
                                        ->required()
                                        ->dehydrateStateUsing(fn (?string $state): float => filled($state) ? (float) $state : 0.0)
                                        ->live(onBlur: true),
                                    TextInput::make('tax_rate')
                                        ->label('ITBMS %')
                                        ->numeric()
                                        ->default(7)
                                        ->dehydrateStateUsing(fn (?string $state): float => filled($state) ? (float) $state : 7.0)
                                        ->suffix('%')
                                        ->live(onBlur: true),
                                    Placeholder::make('line_preview')
                                        ->label('Total línea')
                                        ->content(function (Get $get): string {
                                            $qty = (float) ($get('quantity') ?? 0);
                                            $price = (float) ($get('unit_price') ?? 0);
                                            $taxRate = (float) ($get('tax_rate') ?? 0);
                                            $subtotal = $qty * $price;
                                            $total = $subtotal + ($subtotal * ($taxRate / 100));

                                            return MoneyFormatter::format($total, $get('../../currency'));
                                        }),
                                ])
                                ->columns(3)
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
                        ->schema(static::footerFields())
                        ->columns(2),
                ])
                    ->label('Cotización')
                    ->columnSpanFull()
                    ->contained()
                    ->skippable(false),
            ]);
    }
}
