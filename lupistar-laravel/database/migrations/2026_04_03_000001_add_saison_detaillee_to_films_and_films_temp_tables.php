<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('films', function (Blueprint $table) {
            $table->boolean('saison_detaillee')->nullable();
        });

        Schema::table('films_temp', function (Blueprint $table) {
            $table->boolean('saison_detaillee')->nullable();
        });

        DB::table('films')
            ->whereNull('saison_detaillee')
            ->whereNull('ordre_suite')
            ->whereNotNull('saison')
            ->update(['saison_detaillee' => 1]);

        DB::table('films_temp')
            ->whereNull('saison_detaillee')
            ->whereNull('ordre_suite')
            ->whereNotNull('saison')
            ->update(['saison_detaillee' => 1]);
    }

    public function down(): void
    {
        Schema::table('films', function (Blueprint $table) {
            $table->dropColumn('saison_detaillee');
        });

        Schema::table('films_temp', function (Blueprint $table) {
            $table->dropColumn('saison_detaillee');
        });
    }
};

