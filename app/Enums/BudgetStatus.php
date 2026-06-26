<?php

namespace App\Enums;

enum BudgetStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Issued => 'Emitido',
            self::Archived => 'Archivado',
        };
    }
}
