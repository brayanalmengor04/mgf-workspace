<?php

namespace App\Filament\Resources\BudgetItemTemplates;

use App\Filament\Resources\BudgetItemTemplates\Pages\CreateBudgetItemTemplate;
use App\Filament\Resources\BudgetItemTemplates\Pages\EditBudgetItemTemplate;
use App\Filament\Resources\BudgetItemTemplates\Pages\ListBudgetItemTemplates;
use App\Filament\Resources\BudgetItemTemplates\Schemas\BudgetItemTemplateForm;
use App\Filament\Resources\BudgetItemTemplates\Tables\BudgetItemTemplatesTable;
use App\Models\BudgetItemTemplate;
use App\Support\CrmNavigation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BudgetItemTemplateResource extends Resource
{
    protected static ?string $model = BudgetItemTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmarkSquare;

    protected static string|UnitEnum|null $navigationGroup = CrmNavigation::MODULO_PRESUPUESTO;

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'concepto frecuente';

    protected static ?string $pluralModelLabel = 'Conceptos frecuentes';

    protected static ?string $navigationLabel = 'Conceptos frecuentes';

    protected static ?string $recordTitleAttribute = 'concept';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user ? $query->forUser($user) : $query;
    }

    public static function form(Schema $schema): Schema
    {
        return BudgetItemTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BudgetItemTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBudgetItemTemplates::route('/'),
            'create' => CreateBudgetItemTemplate::route('/create'),
            'edit' => EditBudgetItemTemplate::route('/{record}/edit'),
        ];
    }
}
