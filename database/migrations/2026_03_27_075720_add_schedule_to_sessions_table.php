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
        Schema::table('sessions', function (Blueprint $table) {
            $table->text('description')->nullable()->after('type');
            // Planned start time for scheduled (future) sessions. Null = start immediately.
            $table->timestamp('scheduled_at')->nullable()->after('ended_at');
            // Track whether the 15-minute reminder has already been dispatched
            $table->boolean('reminder_sent')->default(false)->after('scheduled_at');
            $table->index('scheduled_at', 'idx_sessions_scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_scheduled_at');
            $table->dropColumn(['description', 'scheduled_at', 'reminder_sent']);
        });
    }
};
