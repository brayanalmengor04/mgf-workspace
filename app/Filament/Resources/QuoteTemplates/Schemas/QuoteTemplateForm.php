<?php

namespace App\Filament\Resources\QuoteTemplates\Schemas;

use App\Enums\QuotePdfLayout;
use App\Filament\Resources\Concerns\HasPartyFields;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuoteTemplateForm
{
    use HasPartyFields;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('General')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre de plantilla')
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_default')
                            ->label('Plantilla predeterminada'),
                        Toggle::make('is_active')
                            ->label('Activa')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Diseño del PDF')
                    ->description('Personalice el aspecto del PDF generado con esta plantilla.')
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
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Emisor (De)')
                    ->schema(static::partyFields('issuer', 'Nombre empresa / persona'))
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Pie de página')
                    ->schema(static::footerFields())
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
