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
        Schema::table('forum_comments', function (Blueprint $table) {
            $table->foreign(['author_id'], 'fk_forum_comments_author')->references(['id'])->on('membres')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['discussion_id'], 'fk_forum_comments_discussion')->references(['id'])->on('forum_discussions')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['edited_by'], 'fk_forum_comments_edited_by')->references(['id'])->on('membres')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['parent_id'], 'fk_forum_comments_parent')->references(['id'])->on('forum_comments')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_comments', function (Blueprint $table) {
            $table->dropForeign('fk_forum_comments_author');
            $table->dropForeign('fk_forum_comments_discussion');
            $table->dropForeign('fk_forum_comments_edited_by');
            $table->dropForeign('fk_forum_comments_parent');
        });
    }
};
