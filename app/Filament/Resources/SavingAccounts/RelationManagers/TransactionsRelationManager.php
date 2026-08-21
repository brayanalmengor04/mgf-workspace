<?php

namespace App\Filament\Resources\SavingAccounts\RelationManagers;

use App\Enums\SavingsTransactionType;
use App\Filament\Resources\SavingAccounts\Actions\SavingsTransactionActions;
use App\Models\SavingsTransaction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Movimientos';

    protected static ?string $modelLabel = 'movimiento';

    protected static ?string $pluralModelLabel = 'movimientos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (SavingsTransactionType $state): string => $state->label())
                    ->color(fn (SavingsTransactionType $state): string => match ($state) {
                        SavingsTransactionType::Opening, SavingsTransactionType::Deposit => 'success',
                        SavingsTransactionType::Withdrawal => 'danger',
                        SavingsTransactionType::Adjustment => 'gray',
                    }),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->formatStateUsing(function ($state, SavingsTransaction $record): string {
                        $prefix = $record->type === SavingsTransactionType::Withdrawal ? '−' : '+';

                        return $prefix.'$'.number_format((float) $state, 2);
                    }),
                TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(50)
                    ->placeholder('—'),
                TextColumn::make('relatedWithdrawal.id')
                    ->label('Repone retiro')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->headerActions([
                SavingsTransactionActions::deposit(fn (): Model => $this->getOwnerRecord()),
                SavingsTransactionActions::withdraw(fn (): Model => $this->getOwnerRecord()),
                SavingsTransactionActions::replenish(fn (): Model => $this->getOwnerRecord()),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
