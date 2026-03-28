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
        Schema::create('membres_films_list', function (Blueprint $table) {
            $table->integer('membres_id');
            $table->integer('films_id')->index('fk_films');
            $table->integer('note');

            $table->primary(['membres_id', 'films_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membres_films_list');
    }
};
