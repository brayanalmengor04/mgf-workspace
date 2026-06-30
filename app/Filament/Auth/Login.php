<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getTitle(): string|Htmlable
    {
        return 'Iniciar sesión';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Bienvenido';
    }

    public function getSubHeading(): string|Htmlable|null
    {
        return (string) config('seo.site_name', config('app.name'));
    }
}
