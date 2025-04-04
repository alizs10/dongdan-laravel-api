<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create pivot table
        Schema::create('personal_transaction_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('personal_transactions')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('personal_categories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_transaction_category');
    }
};
