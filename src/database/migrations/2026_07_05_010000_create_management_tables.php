<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('management_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('layout')->default('list');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('management_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('management_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('position');
            $table->string('photo_url')->nullable();
            $table->string('email')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $groups = [
            [
                'name' => 'Dewan Pengurus Harian',
                'layout' => 'grid',
                'sort_order' => 1,
                'members' => [
                    ['name' => 'Tinuk Andriyanti Asianto', 'position' => 'Ketua Umum'],
                    ['name' => 'Diah Aryani', 'position' => 'Bendahara'],
                    ['name' => 'Bayu Sulistiyanto Ipung Sutejo', 'position' => 'Program Director'],
                    ['name' => 'Agnes Pujianti', 'position' => 'Sekretariat'],
                    ['name' => 'Wahyu Nugroho', 'position' => 'Sekretariat'],
                ],
            ],
            [
                'name' => 'Dewan Pengawas',
                'layout' => 'list',
                'sort_order' => 2,
                'members' => [
                    ['name' => 'John Sihar Simanjuntak', 'position' => 'Ketua'],
                    ['name' => 'Basuki Suhardiman', 'position' => 'Anggota'],
                    ['name' => 'Intan Rahayu', 'position' => 'Anggota'],
                ],
            ],
            [
                'name' => 'Dewan Riset',
                'layout' => 'list',
                'sort_order' => 3,
                'members' => [
                    ['name' => 'Gerry Firmansyah', 'position' => 'Ketua'],
                    ['name' => 'Bambang Jokonowo', 'position' => 'Anggota'],
                    ['name' => 'Agung Mulyo Widodo', 'position' => 'Anggota'],
                ],
            ],
        ];

        foreach ($groups as $group) {
            $groupId = DB::table('management_groups')->insertGetId([
                'name' => $group['name'],
                'layout' => $group['layout'],
                'sort_order' => $group['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($group['members'] as $index => $member) {
                DB::table('management_members')->insert([
                    'management_group_id' => $groupId,
                    'name' => $member['name'],
                    'position' => $member['position'],
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
        Schema::dropIfExists('management_members');
        Schema::dropIfExists('management_groups');
    }
};
