<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@miempresa.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'proveedor@miempresa.com'],
            [
                'name' => 'Proveedor Demo',
                'password' => Hash::make('password'),
                'role' => UserRole::Provider,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
