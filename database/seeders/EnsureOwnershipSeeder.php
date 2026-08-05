<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnsureOwnershipSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('role', UserRole::Admin)->orderBy('id')->first()
            ?? User::query()->orderBy('id')->first();

        if ($admin === null) {
            return;
        }

        User::query()
            ->where('id', $admin->id)
            ->where('role', '!=', UserRole::Admin)
            ->update(['role' => UserRole::Admin]);

        DB::table('quote_templates')
            ->whereNull('user_id')
            ->update(['user_id' => $admin->id]);
    }
}
