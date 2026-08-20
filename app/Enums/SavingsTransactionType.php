<?php

namespace App\Enums;

enum SavingsTransactionType: string
{
    case Opening = 'opening';
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Apertura',
            self::Deposit => 'Depósito',
            self::Withdrawal => 'Retiro',
            self::Adjustment => 'Ajuste',
        };
    }

    public function signedMultiplier(): int
    {
        return match ($this) {
            self::Opening, self::Deposit => 1,
            self::Withdrawal => -1,
            self::Adjustment => 1,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
