<?php

namespace App\Filament\Resources\SavingAccounts\RelationManagers;

use App\Enums\SavingsTransactionType;
use App\Models\SavingsTransaction;
use App\Services\Savings\SavingsLedgerService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
                Action::make('deposit')
                    ->label('Depositar')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->prefix('$'),
                        DatePicker::make('occurred_at')
                            ->label('Fecha')
                            ->default(now())
                            ->required(),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2),
                    ])
                    ->action(function (array $data, SavingsLedgerService $ledger): void {
                        $account = $this->getOwnerRecord();

                        try {
                            $ledger->recordDeposit(
                                account: $account,
                                amount: (float) $data['amount'],
                                notes: $data['notes'] ?? null,
                                occurredAt: isset($data['occurred_at']) ? \Illuminate\Support\Carbon::parse($data['occurred_at']) : null,
                            );

                            Notification::make()
                                ->title('Depósito registrado')
                                ->success()
                                ->send();
                        } catch (\InvalidArgumentException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('withdraw')
                    ->label('Retirar')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('danger')
                    ->form([
                        TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->prefix('$'),
                        DatePicker::make('occurred_at')
                            ->label('Fecha')
                            ->default(now())
                            ->required(),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2),
                    ])
                    ->action(function (array $data, SavingsLedgerService $ledger): void {
                        $account = $this->getOwnerRecord();

                        try {
                            $ledger->recordWithdrawal(
                                account: $account,
                                amount: (float) $data['amount'],
                                notes: $data['notes'] ?? null,
                                occurredAt: isset($data['occurred_at']) ? \Illuminate\Support\Carbon::parse($data['occurred_at']) : null,
                            );

                            Notification::make()
                                ->title('Retiro registrado')
                                ->success()
                                ->send();
                        } catch (\InvalidArgumentException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('replenish')
                    ->label('Reponer')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form(function (): array {
                        $account = $this->getOwnerRecord();
                        $withdrawals = $account->transactions()
                            ->where('type', SavingsTransactionType::Withdrawal)
                            ->orderByDesc('occurred_at')
                            ->get()
                            ->filter(function (SavingsTransaction $withdrawal): bool {
                                $replenished = (float) $withdrawal->replenishments()->sum('amount');

                                return $replenished < (float) $withdrawal->amount;
                            })
                            ->mapWithKeys(function (SavingsTransaction $withdrawal): array {
                                $replenished = (float) $withdrawal->replenishments()->sum('amount');
                                $pending = (float) $withdrawal->amount - $replenished;
                                $label = $withdrawal->occurred_at->format('d/m/Y')
                                    .' · $'.number_format($pending, 2).' pendiente';

                                if ($withdrawal->notes) {
                                    $label .= ' · '.$withdrawal->notes;
                                }

                                return [$withdrawal->id => $label];
                            })
                            ->all();

                        return [
                            Select::make('withdrawal_id')
                                ->label('Retiro a reponer')
                                ->options($withdrawals)
                                ->required()
                                ->native(false),
                            TextInput::make('amount')
                                ->label('Monto a reponer')
                                ->numeric()
                                ->required()
                                ->minValue(0.01)
                                ->prefix('$'),
                            DatePicker::make('occurred_at')
                                ->label('Fecha')
                                ->default(now())
                                ->required(),
                            Textarea::make('notes')
                                ->label('Notas')
                                ->rows(2),
                        ];
                    })
                    ->action(function (array $data, SavingsLedgerService $ledger): void {
                        $account = $this->getOwnerRecord();
                        $withdrawal = SavingsTransaction::query()->find($data['withdrawal_id']);

                        if ($withdrawal === null) {
                            return;
                        }

                        try {
                            $ledger->recordReplenishment(
                                account: $account,
                                withdrawal: $withdrawal,
                                amount: (float) $data['amount'],
                                notes: $data['notes'] ?? null,
                                occurredAt: isset($data['occurred_at']) ? \Illuminate\Support\Carbon::parse($data['occurred_at']) : null,
                            );

                            Notification::make()
                                ->title('Reposición registrada')
                                ->success()
                                ->send();
                        } catch (\InvalidArgumentException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
