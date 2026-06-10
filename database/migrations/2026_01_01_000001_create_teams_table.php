<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['club', 'national'])->default('club');
            $table->string('country')->nullable();
            $table->string('logo_path')->nullable(); // URL hoặc local path
            $table->unsignedBigInteger('highlightly_id')->nullable()->unique();
            $table->unsignedBigInteger('api_football_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('teams'); }
};
