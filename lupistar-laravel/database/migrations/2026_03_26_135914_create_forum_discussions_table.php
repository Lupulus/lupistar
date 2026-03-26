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
        Schema::create('forum_discussions', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('titre');
            $table->text('description')->nullable();
            $table->integer('category_id')->index('fk_forum_discussions_category');
            $table->integer('author_id')->index('fk_forum_discussions_author');
            $table->boolean('pinned')->nullable()->default(false);
            $table->boolean('locked')->nullable()->default(false);
            $table->integer('views')->nullable()->default(0);
            $table->timestamp('last_comment_at')->nullable();
            $table->integer('last_comment_by')->nullable()->index('fk_forum_discussions_last_comment_by');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();

            $table->index(['category_id', 'pinned', 'updated_at'], 'idx_category_pinned_updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_discussions');
    }
};
