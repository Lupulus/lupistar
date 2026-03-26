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
        Schema::table('films', function (Blueprint $table) {
            $table->foreign(['studio_id'], 'films_ibfk_1')->references(['id'])->on('studios')->onUpdate('cascade')->onDelete('no action');
            $table->foreign(['auteur_id'], 'films_ibfk_2')->references(['id'])->on('auteurs')->onUpdate('cascade')->onDelete('no action');
            $table->foreign(['pays_id'], 'films_ibfk_3')->references(['id'])->on('pays')->onUpdate('cascade')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('films', function (Blueprint $table) {
            $table->dropForeign('films_ibfk_1');
            $table->dropForeign('films_ibfk_2');
            $table->dropForeign('films_ibfk_3');
        });
    }
};
