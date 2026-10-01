<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $database = DB::getDatabaseName();
        $hasLegacyIndex = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', 'payments')
            ->where('index_name', 'payments_school_id_term_unique')
            ->exists();

        if ($hasLegacyIndex) {
            $hasSchoolIndex = DB::table('information_schema.statistics')
                ->where('table_schema', $database)
                ->where('table_name', 'payments')
                ->where('index_name', 'payments_school_id_index')
                ->exists();

            if (! $hasSchoolIndex) {
                DB::statement('ALTER TABLE payments ADD INDEX payments_school_id_index (school_id)');
            }

            DB::statement('ALTER TABLE payments DROP INDEX payments_school_id_term_unique');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $database = DB::getDatabaseName();
        $hasLegacyIndex = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', 'payments')
            ->where('index_name', 'payments_school_id_term_unique')
            ->exists();

        if (! $hasLegacyIndex) {
            DB::statement('ALTER TABLE payments ADD UNIQUE payments_school_id_term_unique (school_id, term)');
        }
    }
};
