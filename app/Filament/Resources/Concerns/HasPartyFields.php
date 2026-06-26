<?php

namespace App\Filament\Resources\Concerns;

use App\Rules\PanamaDv;
use App\Rules\PanamaRuc;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;

trait HasPartyFields
{
    protected static function isTemplateDataLocked(Get $get, string $operation): bool
    {
        return $operation === 'create'
            && filled($get('quote_template_id'))
            && ! (bool) $get('override_template_fields');
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    protected static function partyFields(string $prefix, string $nameLabel, bool $lockWhenFromTemplate = false): array
    {
        $fields = [
            TextInput::make("{$prefix}_name")
                ->label($nameLabel)
                ->required()
                ->maxLength(255),
            TextInput::make("{$prefix}_ruc")
                ->label('RUC / Cédula')
                ->maxLength(30)
                ->rules([new PanamaRuc]),
            Toggle::make("{$prefix}_has_dv")
                ->label('¿Aplica DV?')
                ->live()
                ->default(false),
            TextInput::make("{$prefix}_dv")
                ->label('DV')
                ->maxLength(2)
                ->rules([new PanamaDv])
                ->visible(fn (Get $get): bool => (bool) $get("{$prefix}_has_dv")),
            TextInput::make("{$prefix}_address")
                ->label('Dirección')
                ->maxLength(255),
            TextInput::make("{$prefix}_phone")
                ->label('Teléfono')
                ->tel()
                ->maxLength(50),
            TextInput::make("{$prefix}_email")
                ->label('Correo electrónico')
                ->email()
                ->maxLength(255),
        ];

        if (! $lockWhenFromTemplate) {
            return $fields;
        }

        return array_map(
            fn ($field) => $field
                ->disabled(fn (Get $get, string $operation): bool => static::isTemplateDataLocked($get, $operation))
                ->dehydrated(true),
            $fields,
        );
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    protected static function footerFields(bool $lockWhenFromTemplate = false): array
    {
        $fields = [
            TextInput::make('bank_name')
                ->label('Nombre del banco')
                ->maxLength(255),
            TextInput::make('bank_account_number')
                ->label('Número de cuenta')
                ->maxLength(100),
            TextInput::make('yappy_id')
                ->label('Yappy')
                ->maxLength(100),
            TextInput::make('footer_notes')
                ->label('Notas al pie')
                ->columnSpanFull(),
        ];

        if (! $lockWhenFromTemplate) {
            return $fields;
        }

        return array_map(
            fn ($field) => $field
                ->disabled(fn (Get $get, string $operation): bool => static::isTemplateDataLocked($get, $operation))
                ->dehydrated(true),
            $fields,
        );
    }
}
