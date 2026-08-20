<?php

namespace App\Filament\Resources\SavingAccounts\Schemas;

use App\Enums\BudgetPeriod;
use App\Enums\QuoteCurrency;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SavingAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nombre de la meta')
                    ->required()
                    ->maxLength(120)
                    ->placeholder('Fondo emergencia, Navidad…')
                    ->columnSpanFull(),
                TextInput::make('bank_alias')
                    ->label('Alias del banco')
                    ->maxLength(80)
                    ->placeholder('BAC ahorros, Yappy ahorro…')
                    ->helperText('Usa un alias. No ingreses el número completo de cuenta.'),
                TextInput::make('bank_last_four')
                    ->label('Últimos 4 dígitos')
                    ->maxLength(4)
                    ->placeholder('1234'),
                Select::make('currency')
                    ->label('Moneda')
                    ->options(QuoteCurrency::options())
                    ->default(QuoteCurrency::Usd->value)
                    ->required()
                    ->native(false),
                Select::make('period')
                    ->label('Meta por período')
                    ->options(collect([BudgetPeriod::Biweekly, BudgetPeriod::Monthly, BudgetPeriod::Weekly])
                        ->mapWithKeys(fn (BudgetPeriod $period): array => [$period->value => $period->label()])
                        ->all())
                    ->default(BudgetPeriod::Biweekly->value)
                    ->required()
                    ->native(false),
                TextInput::make('target_per_period')
                    ->label('Monto meta por período')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->dehydrateStateUsing(fn (?string $state): ?float => filled($state) ? (float) $state : null),
                TextInput::make('goal_amount')
                    ->label('Meta total (opcional)')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->dehydrateStateUsing(fn (?string $state): ?float => filled($state) ? (float) $state : null),
                TextInput::make('opening_balance')
                    ->label('Saldo inicial')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->default(0)
                    ->dehydrated(false)
                    ->visibleOn('create')
                    ->helperText('Se registrará como movimiento de apertura al crear la cuenta.'),
                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true)
                    ->columnSpanFull(),
                Placeholder::make('current_balance_display')
                    ->label('Saldo actual')
                    ->content(fn ($record): string => $record !== null
                        ? '$'.number_format((float) $record->current_balance, 2)
                        : '—')
                    ->visibleOn('edit'),
                Placeholder::make('pending_replenishment_display')
                    ->label('Pendiente de reponer')
                    ->content(fn ($record): string => $record !== null
                        ? '$'.number_format((float) $record->pending_replenishment, 2)
                        : '—')
                    ->visibleOn('edit'),
            ]);
    }
}
