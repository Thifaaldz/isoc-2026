<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('province_code', 20)->nullable()->after('province');
            $table->string('city_code', 20)->nullable()->after('city');
            $table->string('district')->nullable()->after('city_code');
            $table->string('district_code', 20)->nullable()->after('district');
            $table->string('village')->nullable()->after('district_code');
            $table->string('village_code', 20)->nullable()->after('village');
        });

        DB::table('schools')
            ->whereIn('province', ['DKI Jakarta', 'DKI JAKARTA'])
            ->update(['province' => 'DKI JAKARTA', 'province_code' => '31']);

        DB::table('schools')
            ->whereIn('city', ['Jakarta Pusat', 'KOTA JAKARTA PUSAT'])
            ->update(['city' => 'KOTA JAKARTA PUSAT', 'city_code' => '3173']);

        DB::table('schools')
            ->whereIn('city', ['Jakarta Selatan', 'KOTA JAKARTA SELATAN'])
            ->update(['city' => 'KOTA JAKARTA SELATAN', 'city_code' => '3171']);

        DB::table('schools')
            ->whereIn('city', ['Jakarta Timur', 'KOTA JAKARTA TIMUR'])
            ->update(['city' => 'KOTA JAKARTA TIMUR', 'city_code' => '3172']);
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'province_code',
                'city_code',
                'district',
                'district_code',
                'village',
                'village_code',
            ]);
        });
    }
};
