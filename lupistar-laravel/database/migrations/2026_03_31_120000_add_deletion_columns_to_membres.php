<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            if (! Schema::hasColumn('membres', 'deletion_scheduled_for')) {
                $table->timestamp('deletion_scheduled_for')->nullable()->after('date_creation');
            }
            if (! Schema::hasColumn('membres', 'deletion_cancel_token')) {
                $table->string('deletion_cancel_token', 128)->nullable()->after('deletion_scheduled_for')->index();
            }
            if (! Schema::hasColumn('membres', 'deletion_requested_at')) {
                $table->timestamp('deletion_requested_at')->nullable()->after('deletion_cancel_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('membres', function (Blueprint $table) {
            if (Schema::hasColumn('membres', 'deletion_requested_at')) {
                $table->dropColumn('deletion_requested_at');
            }
            if (Schema::hasColumn('membres', 'deletion_cancel_token')) {
                $table->dropColumn('deletion_cancel_token');
            }
            if (Schema::hasColumn('membres', 'deletion_scheduled_for')) {
                $table->dropColumn('deletion_scheduled_for');
            }
        });
    }
};
