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
        Schema::table('personal_transactions', function (Blueprint $table) {
            //
            $table->foreignId('savings_goal_id')
                ->nullable()
                ->constrained('personal_savings_goals')
                ->nullOnDelete()
                ->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_transactions', function (Blueprint $table) {
            //
            $table->dropForeign(['savings_goal_id']);
            $table->dropColumn('savings_goal_id');
        });
    }
};
