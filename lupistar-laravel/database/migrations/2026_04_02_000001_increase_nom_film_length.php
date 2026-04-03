<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE films MODIFY nom_film VARCHAR(75)');
            DB::statement('ALTER TABLE films_temp MODIFY nom_film VARCHAR(75)');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE films ALTER COLUMN nom_film TYPE VARCHAR(75)');
            DB::statement('ALTER TABLE films_temp ALTER COLUMN nom_film TYPE VARCHAR(75)');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE films MODIFY nom_film VARCHAR(50)');
            DB::statement('ALTER TABLE films_temp MODIFY nom_film VARCHAR(50)');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE films ALTER COLUMN nom_film TYPE VARCHAR(50)');
            DB::statement('ALTER TABLE films_temp ALTER COLUMN nom_film TYPE VARCHAR(50)');
        }
    }
};

