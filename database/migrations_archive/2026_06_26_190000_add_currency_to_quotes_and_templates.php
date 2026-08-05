<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_templates', function (Blueprint $table) {
            $table->string('currency', 3)->default('PAB')->after('footer_notes');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->string('currency', 3)->default('PAB')->after('footer_notes');
        });
    }

    public function down(): void
    {
        Schema::table('quote_templates', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
