<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_plan_items', function (Blueprint $table) {
            $table->foreignId('savings_account_id')
                ->nullable()
                ->after('paid_at')
                ->constrained('savings_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('budget_plan_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('savings_account_id');
        });
    }
};
