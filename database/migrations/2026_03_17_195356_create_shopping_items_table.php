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
        Schema::create('shopping_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_list_id')
                  ->constrained('shopping_lists')
                  ->cascadeOnDelete();
            $table->foreignId('added_by')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('name', 150);
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('category', 80)->nullable();
            $table->boolean('is_checked')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopping_items');
    }
};
