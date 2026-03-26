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
        Schema::create('films_temp_sous_genres', function (Blueprint $table) {
            $table->integer('film_temp_id');
            $table->integer('sous_genre_id')->index();

            $table->primary(['film_temp_id', 'sous_genre_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('films_temp_sous_genres');
    }
};
