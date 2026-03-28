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
        Schema::create('films_temp', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nom_film', 50);
            $table->enum('categorie', ['Film', 'Animation', 'Anime', 'Série', 'Série d\'\'Animation']);
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->year('date_sortie');
            $table->integer('ordre_suite')->nullable();
            $table->integer('saison')->nullable();
            $table->integer('nbrEpisode')->nullable();
            $table->integer('note_moyenne')->nullable();
            $table->integer('studio_id')->nullable()->index('fk_films_temp_studio');
            $table->integer('auteur_id')->nullable()->index('fk_films_temp_auteur');
            $table->integer('pays_id')->nullable()->index('fk_films_temp_pays');
            $table->integer('propose_par')->index('fk_films_temp_propose_par');
            $table->timestamp('date_proposition')->nullable()->useCurrent();
            $table->enum('statut', ['en_attente', 'approuve', 'rejete'])->nullable()->default('en_attente');
            $table->text('commentaire_admin')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('films_temp');
    }
};
