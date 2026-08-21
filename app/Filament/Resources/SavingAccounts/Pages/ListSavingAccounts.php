<?php

namespace App\Filament\Resources\SavingAccounts\Pages;

use App\Filament\Resources\SavingAccounts\SavingAccountResource;
use App\Filament\Widgets\SavingsAccountGoalChartWidget;
use App\Filament\Widgets\SavingsAccountInsightsWidget;
use App\Filament\Widgets\SavingsAccountMetricsWidget;
use App\Filament\Widgets\SavingsActivityTrendChartWidget;
use App\Models\SavingsAccount;
use App\Services\Savings\SavingsLedgerService;
use App\Support\MoneyFormatter;
use App\Support\SavingsAccountSelection;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class ListSavingAccounts extends ListRecords
{
    protected static string $resource = SavingAccountResource::class;

    public function getHeaderWidgets(): array
    {
        return [
            SavingsAccountMetricsWidget::class,
            SavingsAccountGoalChartWidget::class,
            SavingsAccountInsightsWidget::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            SavingsActivityTrendChartWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deposit')
                ->label('Depositar')
                ->icon(Heroicon::OutlinedArrowDownCircle)
                ->color('success')
                ->modalIcon(Heroicon::OutlinedBanknotes)
                ->modalHeading('Registrar depósito')
                ->modalDescription('Elige la cuenta y confirma el monto. Se reflejará al instante en tus metas.')
                ->modalSubmitActionLabel('Confirmar depósito')
                ->fillForm(fn (): array => [
                    'account_id' => SavingsAccountSelection::id(),
                    'occurred_at' => now()->toDateString(),
                ])
                ->form([
                    Select::make('account_id')
                        ->label('¿En qué cuenta depositas?')
                        ->options(fn (): array => SavingsAccount::query()
                            ->forUser(auth()->user())
                            ->active()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->live(),
                    Placeholder::make('account_snapshot')
                        ->label('Estado de la cuenta')
                        ->content(function (Get $get): string {
                            $accountId = $get('account_id');

                            if ($accountId === null) {
                                return 'Selecciona una cuenta para ver su saldo y meta.';
                            }

                            $account = SavingsAccount::query()
                                ->forUser(auth()->user())
                                ->find($accountId);

                            if ($account === null) {
                                return 'Cuenta no encontrada.';
                            }

                            $metrics = app(SavingsLedgerService::class)->metricsForAccount($account);
                            $lines = [
                                'Saldo: '.MoneyFormatter::format($metrics['balance'], $metrics['currency']),
                            ];

                            if ($metrics['has_goal']) {
                                $lines[] = $metrics['goal_label'].': '.MoneyFormatter::format($metrics['goal_amount'], $metrics['currency']);
                                $lines[] = 'Avance: '.number_format((float) $metrics['goal_progress_percent'], 1).'% · Faltan: '.MoneyFormatter::format($metrics['goal_remaining'], $metrics['currency']);
                            } else {
                                $lines[] = 'Sin meta configurada en esta cuenta.';
                            }

                            return implode("\n", $lines);
                        })
                        ->columnSpanFull(),
                    TextInput::make('amount')
                        ->label('Monto a depositar')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->prefix('$')
                        ->autofocus(),
                    DatePicker::make('occurred_at')
                        ->label('Fecha del depósito')
                        ->default(now())
                        ->required(),
                    Textarea::make('notes')
                        ->label('Notas (opcional)')
                        ->placeholder('Ahorro quincenal, regalo, extra…')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data, SavingsLedgerService $ledger): void {
                    $account = SavingsAccount::query()
                        ->forUser(auth()->user())
                        ->find($data['account_id']);

                    if ($account === null) {
                        Notification::make()
                            ->title('Cuenta no encontrada')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $ledger->recordDeposit(
                            account: $account,
                            amount: (float) $data['amount'],
                            notes: $data['notes'] ?? null,
                            occurredAt: isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : null,
                        );

                        SavingsAccountSelection::set($account->id);
                        $this->dispatch('savings-account-selected', accountId: $account->id);

                        Notification::make()
                            ->title('Depósito registrado')
                            ->body('Se sumaron '.MoneyFormatter::format((float) $data['amount']).' a '.$account->name.'.')
                            ->success()
                            ->send();
                    } catch (\InvalidArgumentException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            CreateAction::make()
                ->label('Nueva cuenta'),
        ];
    }
}
