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
        Schema::create('session_thread_posts', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Foreign keys
            $table->uuid('session_id');
            $table->uuid('author_id');

            // Post content
            $table->text('content');

            // Expiration (48 hours after session ends)
            $table->timestamp('expires_at');

            // Timestamps
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('session_id')->references('id')->on('prayer_sessions')->cascadeOnDelete();
            $table->foreign('author_id')->references('id')->on('users')->cascadeOnDelete();

            // Indexes
            $table->index('session_id');
            $table->index('author_id');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_thread_posts');
    }
};
