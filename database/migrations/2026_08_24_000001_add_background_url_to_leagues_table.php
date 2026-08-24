<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('leagues', 'background_url')) return;

        Schema::table('leagues', function (Blueprint $table) {
            $table->string('background_url')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('leagues', 'background_url')) return;

        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn('background_url');
        });
    }
};
