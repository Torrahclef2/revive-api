<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('sessions')->cascadeOnDelete();
            // Nullable to support anonymous participants (no registered account)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('alias'); // Display name inside the session
            $table->enum('role', ['host', 'participant'])->default('participant');
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_participants');
    }
};
