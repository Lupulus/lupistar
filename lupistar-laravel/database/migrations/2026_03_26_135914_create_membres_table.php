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
        Schema::create('membres', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('titre', 25);
            $table->string('email')->nullable();
            $table->string('photo_profil')->nullable()->default('img/img-profile/profil.png');
            $table->string('restriction', 50)->nullable()->default('Aucune');
            $table->integer('avertissements')->default(0);
            $table->integer('recompenses')->default(0);
            $table->boolean('politique_acceptee')->default(false)->index()->comment('Indique si le membre a accepté la politique de confidentialité (1=accepté, 0=non accepté)');
            $table->integer('max_films_liste_atteint')->nullable()->default(0);
            $table->integer('max_films_approuves_atteint')->nullable()->default(0);
            $table->boolean('demande_promotion')->nullable()->default(false);
            $table->timestamp('date_derniere_verification')->nullable()->useCurrent();
            $table->timestamp('date_creation')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membres');
    }
};
