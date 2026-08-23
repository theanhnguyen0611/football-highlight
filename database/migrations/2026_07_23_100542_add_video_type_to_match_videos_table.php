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
        Schema::table('match_videos', function (Blueprint $table) {
            $table->enum('video_type', ['highlight', 'full_match'])->default('highlight')->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('match_videos', function (Blueprint $table) {
            $table->dropColumn('video_type');
        });
    }
};
