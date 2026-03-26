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
        Schema::create('forum_categories', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nom', 100)->unique();
            $table->text('description')->nullable();
            $table->string('couleur', 7)->nullable()->default('#ff8c00');
            $table->string('icone', 50)->nullable()->default('fas fa-comments');
            $table->integer('ordre')->nullable()->default(0);
            $table->boolean('admin_only')->nullable()->default(false);
            $table->boolean('active')->nullable()->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_categories');
    }
};
