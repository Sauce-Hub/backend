<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->double('quantity');
            $table->string('unit');
            $table->boolean('isAssigned')->default(false);
            $table->foreignId('receipt_id')->nullable()->constrained('receipts', 'receipt_id')->onDelete('cascade');
            $table->foreignId('suggestion_id')->nullable()->constrained('suggestions', 'id')->onDelete('cascade');
        });

        // Add PostgreSQL Check Constraint enforcing exactly one of receipt_id and suggestion_id is NOT NULL
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE ingredients ADD CONSTRAINT check_receipt_or_suggestion CHECK (
                (receipt_id IS NULL AND suggestion_id IS NOT NULL) OR 
                (receipt_id IS NOT NULL AND suggestion_id IS NULL)
            )');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
