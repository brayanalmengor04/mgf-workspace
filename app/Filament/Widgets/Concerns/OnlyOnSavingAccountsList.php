<?php

namespace App\Filament\Widgets\Concerns;

trait OnlyOnSavingAccountsList
{
    public static function canView(): bool
    {
        return auth()->check();
    }
}
