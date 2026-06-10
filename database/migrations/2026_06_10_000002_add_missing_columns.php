<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // matches — thêm thumbnail_url
        Schema::table('matches', function (Blueprint $table) {
            $table->string('thumbnail_url', 500)->nullable()->after('round');
        });

        // match_videos — thêm quality + language
        Schema::table('match_videos', function (Blueprint $table) {
            $table->string('quality', 10)->nullable()->after('local_path');   // 720p, 1080p
            $table->string('language', 5)->default('en')->after('quality');   // en, es, fr...
        });

        // match_events — thêm assist_name
        Schema::table('match_events', function (Blueprint $table) {
            $table->string('assist_name', 100)->nullable()->after('player_name');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('thumbnail_url');
        });
        Schema::table('match_videos', function (Blueprint $table) {
            $table->dropColumn(['quality', 'language']);
        });
        Schema::table('match_events', function (Blueprint $table) {
            $table->dropColumn('assist_name');
        });
    }
};
