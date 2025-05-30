<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personal_savings_goals', function (Blueprint $table) {
            // add status column to personal_savings_goals table, typed boolean
            $table->boolean('status')->default(false)->after('target_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_savings_goals', function (Blueprint $table) {
            // drop status column from personal_savings_goals table
            $table->dropColumn('status');
        });
    }
};
