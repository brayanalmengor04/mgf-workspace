<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    public function getTitle(): string|Htmlable
    {
        return 'Iniciar sesión · '.config('app.brand');
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubHeading(): string|Htmlable|null
    {
        return null;
    }

    public function hasLogo(): bool
    {
        return false;
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()->live(false);
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()->live(false);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('Mantener sesión iniciada')
            ->helperText('Recomendado para la app instalada. Cierra sesión cuando quieras salir.')
            ->default(true)
            ->live(false);
    }
}
