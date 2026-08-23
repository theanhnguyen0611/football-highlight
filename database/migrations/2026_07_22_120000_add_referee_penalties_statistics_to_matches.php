<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->string('referee')->nullable()->after('venue');
            $table->string('score_penalties')->nullable()->after('away_score');
            $table->json('statistics')->nullable()->after('referee');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['referee', 'score_penalties', 'statistics']);
        });
    }
};
