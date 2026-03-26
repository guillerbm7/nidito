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
        Schema::create('calendar_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipe_id')->constrained()->nullOnDelete();
            $table->string('title');
            $table->date('date');
            $table->enum('type', ['lunch', 'dinner', 'task', 'event'])->default('event');
            $table->text('notes')->nullable();
            $table->string('recipe_url', 2048)->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_entries');
    }
};
