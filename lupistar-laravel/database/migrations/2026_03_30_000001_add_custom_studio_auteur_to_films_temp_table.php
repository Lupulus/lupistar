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
        Schema::table('films_temp', function (Blueprint $table) {
            $table->string('nouveau_studio', 30)->nullable()->after('studio_id');
            $table->string('nouveau_auteur', 30)->nullable()->after('auteur_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('films_temp', function (Blueprint $table) {
            $table->dropColumn(['nouveau_studio', 'nouveau_auteur']);
        });
    }
};

