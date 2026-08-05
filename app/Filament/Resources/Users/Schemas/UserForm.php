<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Cuenta')
                    ->description('Al crear un usuario se enviará una invitación por correo para activar su acceso.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Permisos')
                    ->description('Define el rol y si la cuenta está habilitada.')
                    ->schema([
                        Select::make('role')
                            ->label('Rol')
                            ->options(fn (): array => UserRole::assignableOptionsFor(auth()->user()))
                            ->default(UserRole::Provider->value)
                            ->required()
                            ->native(false)
                            ->disabled(fn (): bool => ! auth()->user()?->isStaff())
                            ->helperText(fn (?string $state): ?string => UserRole::tryFrom((string) $state)?->description()),
                        Toggle::make('is_active')
                            ->label('Cuenta activa')
                            ->default(true)
                            ->helperText('Desactiva el acceso sin eliminar al usuario.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
