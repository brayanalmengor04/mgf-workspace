<?php

namespace App\Filament\Resources\Quotes;

use AlizHarb\ActivityLog\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Filament\Resources\Quotes\Pages\ListQuotes;
use App\Filament\Resources\Quotes\Schemas\QuoteForm;
use App\Filament\Resources\Quotes\Tables\QuotesTable;
use App\Models\Quote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Cotizaciones';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'cotización';

    protected static ?string $pluralModelLabel = 'Cotizaciones';

    protected static ?string $navigationLabel = 'Cotizaciones';

    protected static ?string $recordTitleAttribute = 'quote_number';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user ? $query->forUser($user) : $query;
    }

    public static function form(Schema $schema): Schema
    {
        return QuoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotes::route('/'),
            'create' => CreateQuote::route('/create'),
            'edit' => EditQuote::route('/{record}/edit'),
        ];
    }
}
