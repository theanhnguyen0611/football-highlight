<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE match_events MODIFY COLUMN type ENUM('goal','own_goal','yellow_card','red_card','yellow_red_card','subst','penalty') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE match_events MODIFY COLUMN type ENUM('goal','own_goal','yellow_card','red_card','subst','penalty') NOT NULL");
    }
};
