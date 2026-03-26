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
        Schema::create('forum_moderations', function (Blueprint $table) {
            $table->integer('id', true);
            $table->enum('type', ['comment_deleted', 'discussion_moved', 'discussion_locked', 'discussion_deleted', 'user_warned']);
            $table->enum('target_type', ['comment', 'discussion', 'user']);
            $table->integer('target_id');
            $table->integer('moderator_id')->index('fk_forum_moderations_moderator');
            $table->string('reason');
            $table->text('details')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent()->index();

            $table->index(['target_type', 'target_id'], 'idx_target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_moderations');
    }
};
