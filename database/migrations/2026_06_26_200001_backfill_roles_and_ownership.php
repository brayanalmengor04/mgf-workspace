<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminId = DB::table('users')->orderBy('id')->value('id');

        if ($adminId === null) {
            return;
        }

        DB::table('users')->where('id', $adminId)->update(['role' => 'admin']);

        DB::table('quote_templates')->whereNull('user_id')->update(['user_id' => $adminId]);
    }

    public function down(): void
    {
        //
    }
};
