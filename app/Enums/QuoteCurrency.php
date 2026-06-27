<?php

namespace App\Enums;

enum QuoteCurrency: string
{
    case Pab = 'PAB';
    case Usd = 'USD';
    case Eur = 'EUR';
    case Crc = 'CRC';
    case Cop = 'COP';
    case Mxn = 'MXN';
    case Gtq = 'GTQ';
    case Nio = 'NIO';
    case Hnl = 'HNL';
    case Dop = 'DOP';

    public function label(): string
    {
        return match ($this) {
            self::Pab => 'Balboa (PAB)',
            self::Usd => 'Dólar estadounidense (USD)',
            self::Eur => 'Euro (EUR)',
            self::Crc => 'Colón costarricense (CRC)',
            self::Cop => 'Peso colombiano (COP)',
            self::Mxn => 'Peso mexicano (MXN)',
            self::Gtq => 'Quetzal guatemalteco (GTQ)',
            self::Nio => 'Córdoba nicaragüense (NIO)',
            self::Hnl => 'Lempira hondureño (HNL)',
            self::Dop => 'Peso dominicano (DOP)',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Pab => 'B:/',
            self::Usd => '$',
            self::Eur => '€',
            self::Crc => '₡',
            self::Cop => '$',
            self::Mxn => '$',
            self::Gtq => 'Q',
            self::Nio => 'C$',
            self::Hnl => 'L',
            self::Dop => 'RD$',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $currency): array => [$currency->value => $currency->label()])
            ->all();
    }

    public static function resolve(self|string|null $currency): self
    {
        if ($currency instanceof self) {
            return $currency;
        }

        return self::tryFrom((string) $currency) ?? self::Pab;
    }
}
