<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrent();
        });

        DB::table('site_settings')->insert([
            [
                'key' => 'privacy_policy_version',
                'value' => '0',
                'updated_at' => now(),
            ],
            [
                'key' => 'privacy_policy_message',
                'value' => 'La politique de confidentialité a été mise à jour.',
                'updated_at' => now(),
            ],
            [
                'key' => 'privacy_policy_updated_at',
                'value' => (string) now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
