<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_comment_likes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('comment_id');
            $table->unsignedBigInteger('user_id');
            $table->dateTime('created_at');
            $table->unique(['comment_id', 'user_id']);
            $table->index(['comment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_comment_likes');
    }
};

