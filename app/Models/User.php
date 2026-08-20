<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\UserInvitationNotification;
use App\Support\AdminViewMode;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'provider',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isProvider(): bool
    {
        return $this->role === UserRole::Provider;
    }

    public function isStaff(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    public function canViewGlobalData(): bool
    {
        return $this->isSuperAdmin() && ! AdminViewMode::isProviderPreview();
    }

    public function viewsAsProvider(): bool
    {
        return $this->isProvider() || AdminViewMode::isProviderPreview();
    }

    public function viewsAsAdmin(): bool
    {
        return $this->canViewGlobalData();
    }

    public function quoteTemplates(): HasMany
    {
        return $this->hasMany(QuoteTemplate::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'created_by');
    }

    public function budgetPlans(): HasMany
    {
        return $this->hasMany(BudgetPlan::class, 'created_by');
    }

    public function budgetItemTemplates(): HasMany
    {
        return $this->hasMany(BudgetItemTemplate::class);
    }

    public function savingsAccounts(): HasMany
    {
        return $this->hasMany(SavingsAccount::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new UserInvitationNotification($token));
    }
}
