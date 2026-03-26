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
        Schema::table('forum_discussions', function (Blueprint $table) {
            $table->foreign(['author_id'], 'fk_forum_discussions_author')->references(['id'])->on('membres')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['category_id'], 'fk_forum_discussions_category')->references(['id'])->on('forum_categories')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['last_comment_by'], 'fk_forum_discussions_last_comment_by')->references(['id'])->on('membres')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_discussions', function (Blueprint $table) {
            $table->dropForeign('fk_forum_discussions_author');
            $table->dropForeign('fk_forum_discussions_category');
            $table->dropForeign('fk_forum_discussions_last_comment_by');
        });
    }
};
