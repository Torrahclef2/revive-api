<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('sessions')->cascadeOnDelete();
            $table->string('key');   // e.g. "prayer_request", "bible_topic"
            $table->text('value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_meta');
    }
};
