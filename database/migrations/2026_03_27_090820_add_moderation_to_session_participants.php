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
        Schema::table('session_participants', function (Blueprint $table) {
            $table->boolean('is_muted')->default(false)->after('role');
            $table->boolean('is_removed')->default(false)->after('is_muted');
            $table->timestamp('muted_at')->nullable()->after('is_removed');
            $table->timestamp('removed_at')->nullable()->after('muted_at');
        });
    }

    public function down(): void
    {
        Schema::table('session_participants', function (Blueprint $table) {
            $table->dropColumn(['is_muted', 'is_removed', 'muted_at', 'removed_at']);
        });
    }
};
