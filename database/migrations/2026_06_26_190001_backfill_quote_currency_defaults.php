<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('quotes')->whereNull('currency')->update(['currency' => 'PAB']);
        DB::table('quote_templates')->whereNull('currency')->update(['currency' => 'PAB']);
    }

    public function down(): void
    {
        //
    }
};
