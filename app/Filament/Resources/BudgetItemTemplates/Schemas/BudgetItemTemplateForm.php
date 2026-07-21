<?php

namespace App\Filament\Resources\BudgetItemTemplates\Schemas;

use App\Enums\BudgetCategoryType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class BudgetItemTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('category_type')
                    ->label('Categoría')
                    ->options(BudgetCategoryType::options())
                    ->default(BudgetCategoryType::FixedExpense->value)
                    ->required()
                    ->native(false)
                    ->live(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->helperText('Solo los activos aparecen al importar en un presupuesto.'),
                TextInput::make('concept')
                    ->label('Concepto')
                    ->required()
                    ->maxLength(120)
                    ->columnSpanFull()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get): Unique {
                            return $rule
                                ->where('user_id', auth()->id())
                                ->where('category_type', $get('category_type'));
                        },
                    ),
                TextInput::make('notes')
                    ->label('Notas')
                    ->placeholder('Gasto quincenal, Ahorro fijo…')
                    ->maxLength(120)
                    ->columnSpanFull(),
                TextInput::make('default_amount')
                    ->label('Monto predeterminado')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->minValue(0)
                    ->prefix('$')
                    ->dehydrateStateUsing(fn (?string $state): float => filled($state) ? (float) $state : 0.0)
                    ->columnSpanFull(),
            ]);
    }
}
