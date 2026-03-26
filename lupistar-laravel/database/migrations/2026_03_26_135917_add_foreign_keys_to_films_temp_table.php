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
            $table->foreign(['auteur_id'], 'fk_films_temp_auteur')->references(['id'])->on('auteurs')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['pays_id'], 'fk_films_temp_pays')->references(['id'])->on('pays')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['propose_par'], 'fk_films_temp_propose_par')->references(['id'])->on('membres')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['studio_id'], 'fk_films_temp_studio')->references(['id'])->on('studios')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('films_temp', function (Blueprint $table) {
            $table->dropForeign('fk_films_temp_auteur');
            $table->dropForeign('fk_films_temp_pays');
            $table->dropForeign('fk_films_temp_propose_par');
            $table->dropForeign('fk_films_temp_studio');
        });
    }
};
