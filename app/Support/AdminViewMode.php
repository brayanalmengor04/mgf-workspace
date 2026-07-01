<?php

namespace App\Support;

use App\Models\User;

class AdminViewMode
{
    public const SESSION_KEY = 'admin_provider_preview';

    public static function isProviderPreview(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            return false;
        }

        return (bool) session()->get(self::SESSION_KEY, false);
    }

    public static function enableProviderPreview(): void
    {
        static::assertAdmin();

        session()->put(self::SESSION_KEY, true);
    }

    public static function disableProviderPreview(): void
    {
        static::assertAdmin();

        session()->forget(self::SESSION_KEY);
    }

    public static function toggle(): void
    {
        if (static::isProviderPreview()) {
            static::disableProviderPreview();

            return;
        }

        static::enableProviderPreview();
    }

    protected static function assertAdmin(): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            abort(403);
        }
    }
}
