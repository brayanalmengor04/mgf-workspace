<?php

namespace App\Enums;

enum QuotePdfLayout: string
{
    case Classic = 'classic';
    case Modern = 'modern';
    case Minimal = 'minimal';

    public function label(): string
    {
        return match ($this) {
            self::Classic => 'Clásico',
            self::Modern => 'Moderno',
            self::Minimal => 'Minimalista',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Classic => 'Encabezado con acento de color y bloques De/Para.',
            self::Modern => 'Barra superior con logo y datos de la cotización.',
            self::Minimal => 'Diseño limpio en blanco y negro, ideal para impresión.',
        };
    }

    public function view(): string
    {
        return 'quotes.pdf.'.$this->value;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $layout): array => [$layout->value => $layout->label()])
            ->all();
    }
}
