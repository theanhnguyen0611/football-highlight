<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('home_team_id')->constrained('teams');
            $table->foreignId('away_team_id')->constrained('teams');
            $table->foreignId('league_id')->nullable()->constrained('leagues')->nullOnDelete();
            $table->tinyInteger('home_score')->nullable();
            $table->tinyInteger('away_score')->nullable();
            $table->date('match_date');
            $table->time('kick_off_time')->nullable();
            $table->string('venue')->nullable();
            $table->string('round')->nullable();
            $table->enum('status', ['pending', 'downloading', 'ready', 'failed'])->default('pending');
            $table->unsignedBigInteger('highlightly_id')->nullable()->unique();
            $table->unsignedBigInteger('api_football_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('matches'); }
};
