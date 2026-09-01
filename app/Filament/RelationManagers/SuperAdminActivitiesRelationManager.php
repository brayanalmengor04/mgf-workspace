<?php

namespace App\Filament\RelationManagers;

use AlizHarb\ActivityLog\RelationManagers\ActivitiesRelationManager;
use Illuminate\Database\Eloquent\Model;

class SuperAdminActivitiesRelationManager extends ActivitiesRelationManager
{
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
