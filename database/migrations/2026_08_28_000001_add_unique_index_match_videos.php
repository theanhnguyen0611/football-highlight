<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Chống race condition: nhiều queue worker (từ 27/08 numprocs=2) có thể cùng
// lúc gọi MatchVideo::updateOrCreate(['match_id','source']) cho cùng 1 trận
// (CrawlMatchesJob + MapHoofootVideosJob cùng nhắm source=hoofoot) — không có
// unique index thì race tạo ra 2 row trùng, hiển thị/tải video lặp.
return new class extends Migration {
    public function up(): void
    {
        Schema::table('match_videos', function (Blueprint $table) {
            $table->unique(['match_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('match_videos', function (Blueprint $table) {
            $table->dropUnique(['match_id', 'source']);
        });
    }
};
