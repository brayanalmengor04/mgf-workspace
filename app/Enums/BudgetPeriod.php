<?php

namespace App\Enums;

enum BudgetPeriod: string
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Semanal',
            self::Biweekly => 'Quincenal',
            self::Monthly => 'Mensual',
            self::Custom => 'Personalizado',
        };
    }

    public function defaultTitle(): string
    {
        return match ($this) {
            self::Weekly => 'Presupuesto Semanal',
            self::Biweekly => 'Presupuesto Quincenal',
            self::Monthly => 'Presupuesto Mensual',
            self::Custom => 'Presupuesto Personal',
        };
    }

    public function defaultSubtitle(): string
    {
        return 'Estructura de Gastos y Ahorros';
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $period): array => [$period->value => $period->label()])
            ->all();
    }
}
