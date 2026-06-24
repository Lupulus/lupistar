<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration de la table "membres_films_list".
 *
 * C'est la TABLE PIVOT (table d'association) qui matérialise la relation
 * "plusieurs-à-plusieurs" entre les membres et les films : un membre peut
 * noter plusieurs films, et un film peut être noté par plusieurs membres.
 * Elle porte en plus une donnée propre à la relation : la "note".
 *
 * Une migration = un script versionné qui crée/modifie le schéma de la
 * base. Elle permet de recréer une base identique sur n'importe quel
 * environnement avec la commande "php artisan migrate".
 */
return new class extends Migration
{
    /**
     * Crée la table (sens "avant" de la migration).
     */
    public function up(): void
    {
        Schema::create('membres_films_list', function (Blueprint $table) {
            $table->integer('membres_id');                 // référence le membre
            $table->integer('films_id')->index('fk_films'); // référence le film (indexé)
            $table->integer('note');                        // la note attribuée

            // CLÉ PRIMAIRE COMPOSITE : le couple (membre, film) doit être unique.
            // => Un même membre ne peut avoir qu'UNE seule note pour un film donné.
            $table->primary(['membres_id', 'films_id']);
        });
    }

    /**
     * Supprime la table (sens "arrière" : annulation de la migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('membres_films_list');
    }
};
