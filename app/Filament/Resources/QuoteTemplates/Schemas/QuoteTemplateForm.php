<?php

namespace App\Filament\Resources\QuoteTemplates\Schemas;

use App\Enums\QuoteCurrency;
use App\Enums\QuotePdfLayout;
use App\Filament\Resources\Concerns\HasPartyFields;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class QuoteTemplateForm
{
    use HasPartyFields;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Wizard::make([
                    Step::make('General')
                        ->description('Nombre, estado y moneda predeterminada')
                        ->icon(Heroicon::OutlinedCog6Tooth)
                        ->schema([
                            TextInput::make('name')
                                ->label('Nombre de plantilla')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Toggle::make('is_default')
                                ->label('Plantilla predeterminada')
                                ->helperText('Se usará al crear nuevas cotizaciones.'),
                            Toggle::make('is_active')
                                ->label('Activa')
                                ->default(true),
                            Select::make('currency')
                                ->label('Moneda predeterminada')
                                ->options(QuoteCurrency::options())
                                ->default(QuoteCurrency::Pab->value)
                                ->required()
                                ->native(false),
                        ])
                        ->columns(2),
                    Step::make('Diseño PDF')
                        ->description('Logo, colores y estilo del documento')
                        ->icon(Heroicon::OutlinedPaintBrush)
                        ->schema([
                            Select::make('pdf_layout')
                                ->label('Estilo de PDF')
                                ->options(QuotePdfLayout::options())
                                ->default(QuotePdfLayout::Classic->value)
                                ->required()
                                ->native(false),
                            ColorPicker::make('primary_color')
                                ->label('Color principal')
                                ->default('#d97706'),
                            FileUpload::make('logo_path')
                                ->label('Logo')
                                ->image()
                                ->disk('public')
                                ->directory('quote-templates/logos')
                                ->maxSize(2048)
                                ->imageEditor()
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Step::make('Emisor')
                        ->description('Datos fijos del emisor en el PDF')
                        ->icon(Heroicon::OutlinedBuildingOffice)
                        ->schema(static::partyFields('issuer', 'Nombre empresa / persona'))
                        ->columns(2),
                    Step::make('Pago y notas')
                        ->description('Datos bancarios y notas al pie del PDF')
                        ->icon(Heroicon::OutlinedBanknotes)
                        ->schema(static::footerFields())
                        ->columns(2),
                ])
                    ->label('Plantilla de cotización')
                    ->columnSpanFull()
                    ->contained()
                    ->skippable(false),
            ]);
    }
}
