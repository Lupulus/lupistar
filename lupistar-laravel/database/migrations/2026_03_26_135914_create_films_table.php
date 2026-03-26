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
        Schema::create('films', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nom_film', 50);
            $table->enum('categorie', ['Film', 'Animation', 'Anime', 'Série', 'Série d\'\'Animation']);
            $table->text('description')->nullable();
            $table->string('image_path');
            $table->year('date_sortie');
            $table->integer('ordre_suite')->nullable();
            $table->integer('saison')->nullable();
            $table->integer('nbrEpisode')->nullable();
            $table->integer('note_moyenne')->nullable();
            $table->integer('studio_id')->index('studio_id');
            $table->integer('auteur_id')->index('auteur_id');
            $table->integer('pays_id')->index('pays_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('films');
    }
};
