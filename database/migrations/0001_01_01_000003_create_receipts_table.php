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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id('receipt_id');
            $table->string('name');
            $table->string('caption');
            $table->string('category');
            $table->string('estimated_time');
            $table->integer('Calories');
            $table->integer('Fats');
            $table->integer('Carbs');
            $table->integer('Protein');
            $table->timestamp('timestamp')->useCurrent();
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
