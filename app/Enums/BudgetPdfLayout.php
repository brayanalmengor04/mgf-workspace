<?php

namespace App\Enums;

enum BudgetPdfLayout: string
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
            self::Classic => 'Tarjeta de ingreso destacada y tabla por secciones.',
            self::Modern => 'Encabezado con barra de color y bloques visuales.',
            self::Minimal => 'Blanco y negro, ideal para impresión.',
            self::Professional => 'Barra lateral de acento y secciones corporativas.',
            self::Compact => 'Formato denso para muchos conceptos.',
            self::Elegant => 'Marco doble y encabezado centrado.',
        };
    }

    public function view(): string
    {
        return 'budgets.pdf.'.$this->value;
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
