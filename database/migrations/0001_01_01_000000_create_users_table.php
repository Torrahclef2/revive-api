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
        Schema::create('users', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Basic authentication
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken()->nullable();

            // Profile information
            $table->string('username')->unique();
            $table->string('display_name')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('headline')->nullable(); // e.g., 'Intercessor | Teacher'

            // Spiritual information
            $table->string('denomination')->nullable();
            $table->enum('gender', ['male', 'female', 'prefer_not_to_say'])->default('prefer_not_to_say');
            $table->enum('level', ['seeker', 'rising_disciple', 'follower', 'faithful', 'leader'])->default('seeker');

            // Location
            $table->string('location_city')->nullable();
            $table->string('location_country')->nullable();

            // Engagement metrics
            $table->unsignedInteger('xp_points')->default(0);
            $table->unsignedInteger('streak_count')->default(0);
            $table->date('last_active_date')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('email');
            $table->index('username');
            $table->index('location_country');
            $table->index('level');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
