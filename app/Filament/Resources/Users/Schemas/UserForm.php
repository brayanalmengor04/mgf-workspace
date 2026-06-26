<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Cuenta')
                    ->description('Datos de acceso al portal.')
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
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->confirmed()
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                        TextInput::make('password_confirmation')
                            ->label('Confirmar contraseña')
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Permisos')
                    ->description('Define el rol y si la cuenta está habilitada.')
                    ->schema([
                        Select::make('role')
                            ->label('Rol')
                            ->options(UserRole::options())
                            ->default(UserRole::Provider->value)
                            ->required()
                            ->native(false)
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
