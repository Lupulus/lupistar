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
        Schema::table('membres_films_list', function (Blueprint $table) {
            $table->foreign(['films_id'], 'fk_films')->references(['id'])->on('films')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['membres_id'], 'fk_membres')->references(['id'])->on('membres')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membres_films_list', function (Blueprint $table) {
            $table->dropForeign('fk_films');
            $table->dropForeign('fk_membres');
        });
    }
};
