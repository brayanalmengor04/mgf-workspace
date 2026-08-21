<?php

namespace App\Filament\Resources\SavingAccounts\Actions;

use App\Enums\SavingsTransactionType;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Services\Savings\SavingsLedgerService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class SavingsTransactionActions
{
    /**
     * @param  Closure(): SavingsAccount|null  $resolveAccount
     */
    public static function deposit(Closure $resolveAccount): Action
    {
        return self::makeDepositAction()
            ->action(function (array $data, SavingsLedgerService $ledger) use ($resolveAccount): void {
                $account = $resolveAccount();

                if ($account === null) {
                    return;
                }

                self::runDeposit($ledger, $account, $data);
            });
    }

    /**
     * @param  Closure(): SavingsAccount|null  $resolveAccount
     */
    public static function withdraw(Closure $resolveAccount): Action
    {
        return self::makeWithdrawAction()
            ->action(function (array $data, SavingsLedgerService $ledger) use ($resolveAccount): void {
                $account = $resolveAccount();

                if ($account === null) {
                    return;
                }

                self::runWithdraw($ledger, $account, $data);
            });
    }

    /**
     * @param  Closure(): SavingsAccount|null  $resolveAccount
     */
    public static function replenish(Closure $resolveAccount): Action
    {
        return self::makeReplenishAction()
            ->visible(fn (): bool => (float) ($resolveAccount()?->pending_replenishment ?? 0) > 0)
            ->form(fn (): array => self::replenishForm($resolveAccount()))
            ->action(function (array $data, SavingsLedgerService $ledger) use ($resolveAccount): void {
                $account = $resolveAccount();

                if ($account === null) {
                    return;
                }

                self::runReplenish($ledger, $account, $data);
            });
    }

    public static function depositForRecord(): Action
    {
        return self::makeDepositAction()
            ->action(function (SavingsAccount $record, array $data, SavingsLedgerService $ledger): void {
                self::runDeposit($ledger, $record, $data);
            });
    }

    public static function withdrawForRecord(): Action
    {
        return self::makeWithdrawAction()
            ->action(function (SavingsAccount $record, array $data, SavingsLedgerService $ledger): void {
                self::runWithdraw($ledger, $record, $data);
            });
    }

    public static function replenishForRecord(): Action
    {
        return self::makeReplenishAction()
            ->visible(fn (SavingsAccount $record): bool => (float) $record->pending_replenishment > 0)
            ->form(fn (SavingsAccount $record): array => self::replenishForm($record))
            ->action(function (SavingsAccount $record, array $data, SavingsLedgerService $ledger): void {
                self::runReplenish($ledger, $record, $data);
            });
    }

    private static function makeDepositAction(): Action
    {
        return Action::make('deposit')
            ->label('Depositar')
            ->icon('heroicon-o-arrow-down-circle')
            ->color('success')
            ->form(self::depositForm());
    }

    private static function makeWithdrawAction(): Action
    {
        return Action::make('withdraw')
            ->label('Retirar')
            ->icon('heroicon-o-arrow-up-circle')
            ->color('danger')
            ->form(self::withdrawForm());
    }

    private static function makeReplenishAction(): Action
    {
        return Action::make('replenish')
            ->label('Reponer')
            ->icon('heroicon-o-arrow-path')
            ->color('warning');
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function depositForm(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function withdrawForm(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function replenishForm(?SavingsAccount $account): array
    {
        $withdrawals = [];

        if ($account !== null) {
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
        }

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
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function runDeposit(SavingsLedgerService $ledger, SavingsAccount $account, array $data): void
    {
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
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function runWithdraw(SavingsLedgerService $ledger, SavingsAccount $account, array $data): void
    {
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
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function runReplenish(SavingsLedgerService $ledger, SavingsAccount $account, array $data): void
    {
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
    }
}
