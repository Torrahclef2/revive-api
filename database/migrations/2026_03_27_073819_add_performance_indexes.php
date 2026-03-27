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
        // sessions: fast filter for the live-sessions feed
        Schema::table('sessions', function (Blueprint $table) {
            $table->index('status', 'idx_sessions_status');
        });

        // session_participants: the activeParticipants() scope filters on both
        // session_id (FK, already indexed) AND left_at — the composite covers both
        Schema::table('session_participants', function (Blueprint $table) {
            $table->index(['session_id', 'left_at'], 'idx_sp_session_left_at');
            $table->index('user_id', 'idx_sp_user_id');
        });

        // messages: ordered fetch by conversation + time, and mark-as-read filter
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'created_at'], 'idx_msg_conv_created');
            $table->index(['conversation_id', 'read_at', 'sender_id'], 'idx_msg_unread');
        });

        // conversation_participants: used in whereHas lookups by user_id
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->index('user_id', 'idx_cp_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', fn (Blueprint $t) => $t->dropIndex('idx_sessions_status'));
        Schema::table('session_participants', function (Blueprint $t) {
            $t->dropIndex('idx_sp_session_left_at');
            $t->dropIndex('idx_sp_user_id');
        });
        Schema::table('messages', function (Blueprint $t) {
            $t->dropIndex('idx_msg_conv_created');
            $t->dropIndex('idx_msg_unread');
        });
        Schema::table('conversation_participants', fn (Blueprint $t) => $t->dropIndex('idx_cp_user_id'));
    }
};
