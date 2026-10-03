<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            if (! Schema::hasColumn('participants', 'nik')) {
                $table->string('nik', 50)->nullable()->after('nis');
            }
        });

        // NISN/NIM/NIK tidak wajib unik; index biasa di school_id dibuat dulu agar foreign key tetap punya index.
        if (Schema::hasIndex('participants', 'participants_school_id_nis_unique')) {
            Schema::table('participants', function (Blueprint $table) {
                if (! Schema::hasIndex('participants', 'participants_school_id_index')) {
                    $table->index('school_id');
                }
                $table->dropUnique(['school_id', 'nis']);
            });
        }

        // Sebelumnya kolom nis juga menampung NIK untuk peserta umum/karyawan; pindahkan ke kolom nik.
        DB::table('participants')
            ->whereIn('participant_category', ['umum', 'karyawan'])
            ->whereNotNull('nis')
            ->whereNull('nik')
            ->update(['nik' => DB::raw('nis'), 'nis' => null]);

        Schema::table('tutors', function (Blueprint $table) {
            if (! Schema::hasColumn('tutors', 'nik')) {
                $table->string('nik', 50)->nullable()->after('institution');
            }
            if (! Schema::hasColumn('tutors', 'npwp')) {
                $table->string('npwp', 30)->nullable()->after('nik');
            }
            if (! Schema::hasColumn('tutors', 'bank_name')) {
                $table->string('bank_name', 100)->nullable()->after('npwp');
            }
            if (! Schema::hasColumn('tutors', 'bank_account_number')) {
                $table->string('bank_account_number', 50)->nullable()->after('bank_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            foreach (['bank_account_number', 'bank_name', 'npwp', 'nik'] as $column) {
                if (Schema::hasColumn('tutors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('participants', 'nik')) {
            DB::table('participants')
                ->whereIn('participant_category', ['umum', 'karyawan'])
                ->whereNull('nis')
                ->update(['nis' => DB::raw('nik')]);

            Schema::table('participants', function (Blueprint $table) {
                $table->dropColumn('nik');
            });
        }
    }
};
