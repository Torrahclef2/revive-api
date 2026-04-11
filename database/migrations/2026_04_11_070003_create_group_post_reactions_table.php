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
        Schema::create('group_post_reactions', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Foreign keys
            $table->uuid('post_id');
            $table->uuid('user_id');

            // Reaction type
            $table->enum('reaction', ['amen', 'heart', 'pray']);

            // Timestamp (no updated_at)
            $table->timestamp('created_at')->useCurrent();

            // Foreign key constraints
            $table->foreign('post_id')->references('id')->on('group_posts')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            // Indexes
            $table->index('post_id');
            $table->index('user_id');

            // Unique constraint to prevent duplicate reactions per user per post
            $table->unique(['post_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_post_reactions');
    }
};
