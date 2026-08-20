<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('bank_alias')->nullable();
            $table->string('bank_last_four', 4)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('target_per_period', 12, 2)->nullable();
            $table->string('period')->default('biweekly');
            $table->decimal('goal_amount', 12, 2)->nullable();
            $table->decimal('current_balance', 12, 2)->default(0);
            $table->decimal('pending_replenishment', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_accounts');
    }
};
