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
        Schema::create('notifications', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id')->index();
            $table->string('titre');
            $table->text('message');
            $table->string('type', 50)->nullable()->default('film_rejection');
            $table->boolean('lu')->nullable()->default(false)->index();
            $table->timestamp('date_creation')->nullable()->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
