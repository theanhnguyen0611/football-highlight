<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE match_videos MODIFY COLUMN status ENUM('pending','downloading','ready','failed','error') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE match_videos MODIFY COLUMN status ENUM('pending','downloading','ready','failed') NOT NULL DEFAULT 'pending'");
    }
};
