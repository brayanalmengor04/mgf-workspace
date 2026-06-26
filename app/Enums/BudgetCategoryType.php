<?php

namespace App\Enums;

enum BudgetCategoryType: string
{
    case FixedExpense = 'fixed_expense';
    case Savings = 'savings';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FixedExpense => 'Gastos fijos',
            self::Savings => 'Ahorros',
            self::Other => 'Otros',
        };
    }

    public function sectionLabel(): string
    {
        return match ($this) {
            self::FixedExpense => 'Gastos fijos',
            self::Savings => 'Ahorros (fijos y temporales)',
            self::Other => 'Otros conceptos',
        };
    }

    public function sectionLetter(): string
    {
        return match ($this) {
            self::FixedExpense => 'A',
            self::Savings => 'B',
            self::Other => 'C',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FixedExpense => '#64748b',
            self::Savings => '#059669',
            self::Other => '#7c3aed',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::FixedExpense => '🏠',
            self::Savings => '💰',
            self::Other => '📦',
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

    public static function resolve(self|string|null $type): self
    {
        if ($type instanceof self) {
            return $type;
        }

        return self::tryFrom((string) $type) ?? self::Other;
    }
}
