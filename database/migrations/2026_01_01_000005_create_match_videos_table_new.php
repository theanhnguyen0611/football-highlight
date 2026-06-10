<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('match_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->string('source'); // hoofoot, highlightly, youtube, etc
            $table->string('source_url'); // URL gốc
            $table->string('local_path')->nullable(); // HLS local path
            $table->enum('status', ['pending', 'downloading', 'ready', 'failed'])->default('pending');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->decimal('file_size_mb', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('match_videos'); }
};
