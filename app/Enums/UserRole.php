<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Provider = 'provider';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Provider => 'Proveedor',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Acceso total: usuarios, auditoría y todas las cotizaciones.',
            self::Provider => 'Gestiona solo sus plantillas y cotizaciones.',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Provider => 'info',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role): array => [$role->value => $role->label()])
            ->all();
    }
}
