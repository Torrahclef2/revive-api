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
            $table->integer('reputation_score')->default(100)->after('ban_reason');
            $table->boolean('is_banned')->default(false)->after('reputation_score');
            $table->timestamp('banned_until')->nullable()->after('is_banned');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reputation_score', 'is_banned', 'banned_until']);
        });
    }
};
