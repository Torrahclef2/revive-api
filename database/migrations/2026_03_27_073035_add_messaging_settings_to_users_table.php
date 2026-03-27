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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('headline');
            // everyone = anyone can message, verified_only = only verified users, disabled = no messages
            $table->enum('messaging_privacy', ['everyone', 'verified_only', 'disabled'])
                  ->default('everyone')
                  ->after('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_verified', 'messaging_privacy']);
        });
    }
};
