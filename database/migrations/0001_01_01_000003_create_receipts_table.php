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
            $table->string('caption')->nullable();
            $table->enum('category', ['BREAKFAST', 'LUNCH', 'DINNER', 'SWEETS', 'HOT DRINKS', 'ICED DRINKS']);
            $table->string('image_url');
            $table->integer('estimated_time')->unsigned()->default(0);
            $table->integer('Calories')->unsigned()->default(0);
            $table->integer('Fats')->unsigned()->default(0);
            $table->integer('Carbs')->unsigned()->default(0);
            $table->integer('Protein')->unsigned()->default(0);
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
