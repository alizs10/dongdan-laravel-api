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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('payer_id')->nullable()->constrained('event_members')->onDelete('cascade');
            $table->foreignId('transmitter_id')->nullable()->constrained('event_members')->onDelete('cascade');
            $table->foreignId('receiver_id')->nullable()->constrained('event_members')->onDelete('cascade');

            // $table->string('name');
            $table->text('description');
            $table->date('date');
            $table->bigInteger('amount');
            $table->enum('type', ['expend', 'transfer']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
