<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Provider = 'provider';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrador',
            self::Admin => 'Administrador',
            self::Provider => 'Proveedor',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Acceso global: usuarios, auditoría completa y todos los registros.',
            self::Admin => 'Acceso operativo completo, limitado a sus propios registros.',
            self::Provider => 'Gestiona solo sus plantillas, cotizaciones y presupuestos.',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SuperAdmin => 'danger',
            self::Admin => 'warning',
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

    /**
     * Roles that the given user may assign when creating or editing accounts.
     *
     * @return array<string, string>
     */
    public static function assignableOptionsFor(?\App\Models\User $actor): array
    {
        if ($actor?->isStaff()) {
            return self::options();
        }

        return [];
    }
}
