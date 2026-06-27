<?php

namespace App\Enums;

enum QuotePdfLayout: string
{
    case Classic = 'classic';
    case Modern = 'modern';
    case Minimal = 'minimal';
    case Professional = 'professional';
    case Compact = 'compact';
    case Elegant = 'elegant';

    public function label(): string
    {
        return match ($this) {
            self::Classic => 'Clásico',
            self::Modern => 'Moderno',
            self::Minimal => 'Minimalista',
            self::Professional => 'Profesional',
            self::Compact => 'Compacto',
            self::Elegant => 'Elegante',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Classic => 'Encabezado con acento de color y bloques De/Para.',
            self::Modern => 'Barra superior con logo y datos de la cotización.',
            self::Minimal => 'Diseño limpio en blanco y negro, ideal para impresión.',
            self::Professional => 'Encabezado corporativo con barra lateral y secciones definidas.',
            self::Compact => 'Formato denso con tablas ajustadas, ideal para muchos ítems.',
            self::Elegant => 'Tipografía refinada con bordes dobles y composición centrada.',
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
