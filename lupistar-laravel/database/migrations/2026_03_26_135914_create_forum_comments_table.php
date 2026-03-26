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
        Schema::create('forum_comments', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('discussion_id')->index('fk_forum_comments_discussion');
            $table->integer('author_id')->index('fk_forum_comments_author');
            $table->text('content');
            $table->integer('parent_id')->nullable()->index('fk_forum_comments_parent');
            $table->timestamp('edited_at')->nullable();
            $table->integer('edited_by')->nullable()->index('fk_forum_comments_edited_by');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['discussion_id', 'created_at'], 'idx_discussion_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_comments');
    }
};
