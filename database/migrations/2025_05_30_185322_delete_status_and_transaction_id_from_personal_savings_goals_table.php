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
            // delete status and transaction_id from personal_savings_goals table
            $table->dropForeign(['transaction_id']);
            $table->dropColumn('transaction_id');
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_savings_goals', function (Blueprint $table) {
            //
            $table->boolean('status')->default(false)->after('due_date'); // Assuming status is a boolean
            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('personal_transactions')
                ->nullOnDelete()
                ->after('due_date'); // Re-adding transaction_id with foreign key constraint
        });
    }
};
