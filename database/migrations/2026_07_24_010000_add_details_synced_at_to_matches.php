<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->timestamp('details_synced_at')->nullable()->after('match_status');
            $table->index('details_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex(['details_synced_at']);
            $table->dropColumn('details_synced_at');
        });
    }
};
