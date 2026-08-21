<?php

namespace App\Enums;

enum SavingsProjectionCadence: string
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Semanal',
            self::Biweekly => 'Quincenal',
            self::Monthly => 'Mensual',
            self::Quarterly => 'Trimestral',
        };
    }

    public function frequencyAdverb(): string
    {
        return match ($this) {
            self::Weekly => 'semanalmente',
            self::Biweekly => 'quincenalmente',
            self::Monthly => 'mensualmente',
            self::Quarterly => 'trimestralmente',
        };
    }

    public function frequencyNoun(): string
    {
        return match ($this) {
            self::Weekly => 'semana',
            self::Biweekly => 'quincena',
            self::Monthly => 'mes',
            self::Quarterly => 'trimestre',
        };
    }

    public function intervalDays(): int
    {
        return match ($this) {
            self::Weekly => 7,
            self::Biweekly => 14,
            self::Monthly => 30,
            self::Quarterly => 90,
        };
    }

    public function timePhrase(int $periods): string
    {
        if ($periods <= 1) {
            return 'en tu próximo depósito ('.$this->frequencyNoun().')';
        }

        return match ($this) {
            self::Weekly => "en ~{$periods} semana(s)",
            self::Biweekly => "en ~{$periods} quincena(s)",
            self::Monthly => "en ~{$periods} mes(es)",
            self::Quarterly => "en ~{$periods} trimestre(s)",
        };
    }

    public function addPeriods(\Illuminate\Support\Carbon $from, int $periods): \Illuminate\Support\Carbon
    {
        return match ($this) {
            self::Weekly => $from->copy()->addWeeks($periods),
            self::Biweekly => $from->copy()->addDays($periods * 14),
            self::Monthly => $from->copy()->addMonths($periods),
            self::Quarterly => $from->copy()->addMonths($periods * 3),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $cadence): array => [$cadence->value => $cadence->label()])
            ->all();
    }
}
