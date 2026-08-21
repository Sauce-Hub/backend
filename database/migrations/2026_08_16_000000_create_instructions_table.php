<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('instructions', function (Blueprint $table) {
            $table->id();
            $table->integer('step_number');
            $table->text('instruction');
            $table->foreignId('receipt_id')->nullable()->constrained('receipts', 'receipt_id')->onDelete('cascade');
            $table->foreignId('suggestion_id')->nullable()->constrained('suggestions', 'id')->onDelete('cascade');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE instructions ADD CONSTRAINT check_instruction_receipt_or_suggestion CHECK (
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
        Schema::dropIfExists('instructions');
    }
};
