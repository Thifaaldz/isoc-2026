<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('logo_url')->nullable();
            $table->string('url')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $groups = [
            [
                'name' => 'GLOBAL PARTNER',
                'sort_order' => 1,
                'partners' => [
                    ['name' => 'ISOC Foundation'],
                ],
            ],
            [
                'name' => 'NATIONAL PARTNERS',
                'sort_order' => 2,
                'partners' => [
                    ['name' => 'BPSDM Komdigi'],
                    ['name' => 'Ditjen Akselerasi ID'],
                    ['name' => 'PANDI'],
                    ['name' => 'APJII'],
                    ['name' => 'RTIK'],
                    ['name' => 'ID-NIC'],
                ],
            ],
        ];

        foreach ($groups as $group) {
            $groupId = DB::table('partner_groups')->insertGetId([
                'name' => $group['name'],
                'sort_order' => $group['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($group['partners'] as $index => $partner) {
                DB::table('partners')->insert([
                    'partner_group_id' => $groupId,
                    'name' => $partner['name'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
        Schema::dropIfExists('partner_groups');
    }
};
