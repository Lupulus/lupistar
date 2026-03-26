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
        Schema::table('films_temp_sous_genres', function (Blueprint $table) {
            $table->foreign(['film_temp_id'], 'fk_films_temp_sous_genres_film')->references(['id'])->on('films_temp')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['sous_genre_id'], 'fk_films_temp_sous_genres_genre')->references(['id'])->on('sous_genres')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('films_temp_sous_genres', function (Blueprint $table) {
            $table->dropForeign('fk_films_temp_sous_genres_film');
            $table->dropForeign('fk_films_temp_sous_genres_genre');
        });
    }
};
