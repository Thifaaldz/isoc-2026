<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Logo mitra lama tersimpan di folder public/ (bukan disk public) sehingga tidak tampil di menu
     * Mitra Event dan bisa hilang saat mitra diedit. Pindahkan ke disk public seperti logo hasil upload.
     */
    public function up(): void
    {
        DB::table('partners')->whereNotNull('logo_path')->get(['id', 'logo_path'])->each(function (object $partner): void {
            $path = ltrim((string) $partner->logo_path, '/');

            if ($path === '' || str_starts_with($path, 'http') || Storage::disk('public')->exists($path) || ! is_file(public_path($path))) {
                return;
            }

            $target = 'partners/' . basename($path);
            Storage::disk('public')->put($target, file_get_contents(public_path($path)));

            DB::table('partners')->where('id', $partner->id)->update(['logo_path' => $target, 'updated_at' => now()]);
        });
    }

    public function down(): void
    {
        //
    }
};
