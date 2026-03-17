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
            $table->id();
            $table->foreignId('shopping_list_id')
                  ->constrained('shopping_lists')
                  ->cascadeOnDelete();
            $table->string('image_path', 512)->nullable();
            $table->decimal('amount', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->date('purchased_at');
            $table->timestamp('created_at')->useCurrent();
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
