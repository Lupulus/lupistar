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
        Schema::create('films_sous_genres', function (Blueprint $table) {
            $table->integer('film_id');
            $table->integer('sous_genre_id')->index();

            $table->primary(['film_id', 'sous_genre_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('films_sous_genres');
    }
};
