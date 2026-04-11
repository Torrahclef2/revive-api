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
        Schema::create('prayer_sessions', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Foreign key
            $table->uuid('host_id');

            // Session information
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('purpose', ['prayer', 'study']);
            $table->enum('template', ['intercessory_prayer', 'scripture_study', 'praise_worship', 'open']);
            $table->enum('visibility', ['circle_only', 'open', 'anonymous']);
            $table->enum('status', ['upcoming', 'admitting', 'live', 'ended'])->default('upcoming');
            $table->enum('gender_preference', ['any', 'male', 'female'])->default('any');

            // Location
            $table->string('location_city')->nullable();
            $table->string('location_country')->nullable();

            // Capacity
            $table->unsignedTinyInteger('max_members');

            // Timing
            $table->timestamp('scheduled_at');
            $table->timestamp('live_started_at')->nullable();
            $table->timestamp('live_ended_at')->nullable();
            $table->unsignedTinyInteger('duration_minutes')->default(60);

            // Agora WebRTC details
            $table->string('agora_channel_name')->unique();

            // Timestamps
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('host_id')->references('id')->on('users')->cascadeOnDelete();

            // Indexes for common queries
            $table->index('status');
            $table->index('visibility');
            $table->index('location_country');
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prayer_sessions');
    }
};
