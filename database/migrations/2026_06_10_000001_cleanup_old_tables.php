<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Xóa bảng không cần thiết
        Schema::dropIfExists('match_player_stats');
        Schema::dropIfExists('match_lineups');
        Schema::dropIfExists('match_statistics');

        // Xóa cột api_football_id khỏi teams
        if (Schema::hasColumn('teams', 'api_football_id')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->dropColumn('api_football_id');
            });
        }

        // Xóa cột api_football_id khỏi matches
        if (Schema::hasColumn('matches', 'api_football_id')) {
            Schema::table('matches', function (Blueprint $table) {
                $table->dropColumn('api_football_id');
            });
        }

        // Xóa các cột cũ không cần trong matches
        Schema::table('matches', function (Blueprint $table) {
            if (Schema::hasColumn('matches', 'match_url')) $table->dropColumn('match_url');
            if (Schema::hasColumn('matches', 'source'))    $table->dropColumn('source');
            if (Schema::hasColumn('matches', 'title'))     $table->dropColumn('title');
        });

        // Xóa primary_color, secondary_color trong teams nếu còn
        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'primary_color'))   $table->dropColumn('primary_color');
            if (Schema::hasColumn('teams', 'secondary_color')) $table->dropColumn('secondary_color');
            if (Schema::hasColumn('teams', 'api_football_name')) $table->dropColumn('api_football_name');
        });
    }

    public function down(): void {}
};
