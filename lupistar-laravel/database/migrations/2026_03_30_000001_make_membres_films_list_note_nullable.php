<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE membres_films_list MODIFY note INT NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE membres_films_list ALTER COLUMN note DROP NOT NULL');

            return;
        }

        if ($driver === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            DB::statement('
                CREATE TABLE membres_films_list__new (
                    membres_id INTEGER NOT NULL,
                    films_id INTEGER NOT NULL,
                    note INTEGER NULL,
                    PRIMARY KEY (membres_id, films_id),
                    FOREIGN KEY (films_id) REFERENCES films(id) ON UPDATE NO ACTION ON DELETE CASCADE,
                    FOREIGN KEY (membres_id) REFERENCES membres(id) ON UPDATE NO ACTION ON DELETE CASCADE
                )
            ');

            DB::statement('
                INSERT INTO membres_films_list__new (membres_id, films_id, note)
                SELECT membres_id, films_id, note FROM membres_films_list
            ');

            DB::statement('DROP TABLE membres_films_list');
            DB::statement('ALTER TABLE membres_films_list__new RENAME TO membres_films_list');

            DB::statement('CREATE INDEX fk_films ON membres_films_list (films_id)');
            DB::statement('CREATE INDEX fk_membres ON membres_films_list (membres_id)');

            Schema::enableForeignKeyConstraints();

            return;
        }

        DB::statement('ALTER TABLE membres_films_list ALTER COLUMN note DROP NOT NULL');
    }

    public function down(): void
    {
        DB::table('membres_films_list')->whereNull('note')->update(['note' => 0]);

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE membres_films_list MODIFY note INT NOT NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE membres_films_list ALTER COLUMN note SET NOT NULL');

            return;
        }

        if ($driver === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            DB::statement('
                CREATE TABLE membres_films_list__new (
                    membres_id INTEGER NOT NULL,
                    films_id INTEGER NOT NULL,
                    note INTEGER NOT NULL,
                    PRIMARY KEY (membres_id, films_id),
                    FOREIGN KEY (films_id) REFERENCES films(id) ON UPDATE NO ACTION ON DELETE CASCADE,
                    FOREIGN KEY (membres_id) REFERENCES membres(id) ON UPDATE NO ACTION ON DELETE CASCADE
                )
            ');

            DB::statement('
                INSERT INTO membres_films_list__new (membres_id, films_id, note)
                SELECT membres_id, films_id, note FROM membres_films_list
            ');

            DB::statement('DROP TABLE membres_films_list');
            DB::statement('ALTER TABLE membres_films_list__new RENAME TO membres_films_list');

            DB::statement('CREATE INDEX fk_films ON membres_films_list (films_id)');
            DB::statement('CREATE INDEX fk_membres ON membres_films_list (membres_id)');

            Schema::enableForeignKeyConstraints();

            return;
        }

        DB::statement('ALTER TABLE membres_films_list ALTER COLUMN note SET NOT NULL');
    }
};
