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
        Schema::table('films_sous_genres', function (Blueprint $table) {
            $table->foreign(['film_id'], 'films_sous_genres_ibfk_1')->references(['id'])->on('films')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['sous_genre_id'], 'films_sous_genres_ibfk_2')->references(['id'])->on('sous_genres')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('films_sous_genres', function (Blueprint $table) {
            $table->dropForeign('films_sous_genres_ibfk_1');
            $table->dropForeign('films_sous_genres_ibfk_2');
        });
    }
};
