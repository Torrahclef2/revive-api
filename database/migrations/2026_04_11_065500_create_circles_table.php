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
        Schema::create('circles', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Foreign keys
            $table->uuid('requester_id');
            $table->uuid('receiver_id');

            // Status
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');

            // Timestamps
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('requester_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('receiver_id')->references('id')->on('users')->cascadeOnDelete();

            // Indexes
            $table->index('requester_id');
            $table->index('receiver_id');

            // Unique constraint to prevent duplicate requests
            $table->unique(['requester_id', 'receiver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circles');
    }
};
