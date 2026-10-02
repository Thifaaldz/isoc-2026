<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        $participantIndexExists = collect(DB::select('show indexes from certificates'))
            ->contains(fn ($index) => $index->Key_name === 'certificates_participant_id_index');

        if (! $participantIndexExists) {
            DB::statement('alter table certificates add index certificates_participant_id_index (participant_id)');
        }

        $indexExists = collect(DB::select('show indexes from certificates'))
            ->contains(fn ($index) => $index->Key_name === 'certificates_participant_id_unique');

        if ($indexExists) {
            DB::statement('alter table certificates drop index certificates_participant_id_unique');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        $indexExists = collect(DB::select('show indexes from certificates'))
            ->contains(fn ($index) => $index->Key_name === 'certificates_participant_id_unique');

        if (! $indexExists) {
            DB::statement('alter table certificates add unique certificates_participant_id_unique (participant_id)');
        }

        $participantIndexExists = collect(DB::select('show indexes from certificates'))
            ->contains(fn ($index) => $index->Key_name === 'certificates_participant_id_index');

        if ($participantIndexExists) {
            DB::statement('alter table certificates drop index certificates_participant_id_index');
        }
    }
};
