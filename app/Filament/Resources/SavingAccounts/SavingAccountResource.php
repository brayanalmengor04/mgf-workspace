<?php

namespace App\Filament\Resources\SavingAccounts;

use App\Filament\Resources\SavingAccounts\Pages\CreateSavingAccount;
use App\Filament\Resources\SavingAccounts\Pages\EditSavingAccount;
use App\Filament\Resources\SavingAccounts\Pages\ListSavingAccounts;
use App\Filament\Resources\SavingAccounts\RelationManagers\TransactionsRelationManager;
use App\Filament\Resources\SavingAccounts\Schemas\SavingAccountForm;
use App\Filament\Resources\SavingAccounts\Tables\SavingAccountsTable;
use App\Models\SavingsAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SavingAccountResource extends Resource
{
    protected static ?string $model = SavingsAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Finanzas personales';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'cuenta de ahorro';

    protected static ?string $pluralModelLabel = 'Cuentas de ahorro';

    protected static ?string $navigationLabel = 'Ahorros';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        return $user ? $query->forUser($user) : $query;
    }

    public static function form(Schema $schema): Schema
    {
        return SavingAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SavingAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSavingAccounts::route('/'),
            'create' => CreateSavingAccount::route('/create'),
            'edit' => EditSavingAccount::route('/{record}/edit'),
        ];
    }
}
