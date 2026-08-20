<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('savings_account_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->date('occurred_at');
            $table->text('notes')->nullable();
            $table->foreignId('budget_plan_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('related_withdrawal_id')->nullable()->constrained('savings_transactions')->nullOnDelete();
            $table->timestamps();

            $table->unique('budget_plan_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_transactions');
    }
};
