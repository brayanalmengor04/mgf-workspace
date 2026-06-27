<?php

namespace App\Filament\Resources\BudgetPlans;

use AlizHarb\ActivityLog\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\BudgetPlans\Pages\CreateBudgetPlan;
use App\Filament\Resources\BudgetPlans\Pages\EditBudgetPlan;
use App\Filament\Resources\BudgetPlans\Pages\ListBudgetPlans;
use App\Filament\Resources\BudgetPlans\Pages\PreviewBudgetPlan;
use App\Filament\Resources\BudgetPlans\Schemas\BudgetPlanForm;
use App\Filament\Resources\BudgetPlans\Tables\BudgetPlansTable;
use App\Models\BudgetPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BudgetPlanResource extends Resource
{
    protected static ?string $model = BudgetPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|UnitEnum|null $navigationGroup = 'Finanzas personales';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'presupuesto';

    protected static ?string $pluralModelLabel = 'Presupuestos';

    protected static ?string $navigationLabel = 'Presupuestos';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user ? $query->forUser($user) : $query;
    }

    public static function form(Schema $schema): Schema
    {
        return BudgetPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BudgetPlansTable::configure($table);
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
            'index' => ListBudgetPlans::route('/'),
            'create' => CreateBudgetPlan::route('/create'),
            'edit' => EditBudgetPlan::route('/{record}/edit'),
            'preview' => PreviewBudgetPlan::route('/{record}/preview'),
        ];
    }
}
