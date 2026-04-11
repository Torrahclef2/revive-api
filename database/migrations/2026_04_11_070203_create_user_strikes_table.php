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
        Schema::create('user_strikes', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Foreign keys
            $table->uuid('user_id');
            $table->uuid('reported_by')->nullable();

            // Strike details
            $table->text('reason');

            // Moderation
            $table->boolean('reviewed')->default(false);

            // Timestamp (created_at only)
            $table->timestamp('created_at')->useCurrent();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reported_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index('user_id');
            $table->index('reviewed');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_strikes');
    }
};
