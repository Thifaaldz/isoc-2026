<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Mitra Event resmi beserta logonya (sumber: docs/mitra, sudah dibuat transparan di public/images/partners).
 * Logo mitra yang dipilih pada event otomatis tampil di sertifikat peserta.
 */
class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPartners();
    }

    /** @return Collection<int, Partner> */
    public function seedPartners(): Collection
    {
        return collect($this->partners())
            ->map(fn (array $partner) => Partner::query()->updateOrCreate(
                ['name' => $partner['name']],
                [...$partner, 'logo_path' => $this->storeLogo($partner['logo_path']), 'status' => 'active'],
            ));
    }

    private function storeLogo(string $publicPath): string
    {
        $target = 'partners/' . basename($publicPath);

        Storage::disk('public')->put($target, file_get_contents(public_path($publicPath)));

        return $target;
    }

    /** @return array<int, array<string, string|null>> */
    private function partners(): array
    {
        return [
            [
                'name' => 'ISOC Indonesia Jakarta Chapter',
                'category' => 'national',
                'subtitle' => 'Internet Society Indonesia Jakarta Chapter',
                'logo_path' => 'images/partners/isoc-jakarta.png',
                'website_url' => 'https://isoc.id',
            ],
            [
                'name' => 'Kementerian Komunikasi dan Digital',
                'category' => 'national',
                'subtitle' => 'Komdigi Republik Indonesia',
                'logo_path' => 'images/partners/komdigi.png',
                'website_url' => 'https://www.komdigi.go.id',
            ],
            [
                'name' => 'Relawan TIK Indonesia',
                'category' => 'community',
                'subtitle' => 'Relawan Teknologi Informasi dan Komunikasi',
                'logo_path' => 'images/partners/relawan-tik.png',
                'website_url' => 'https://relawantik.or.id',
            ],
            [
                'name' => 'APJII',
                'category' => 'national',
                'subtitle' => 'Asosiasi Penyelenggara Jasa Internet Indonesia',
                'logo_path' => 'images/partners/apjii.png',
                'website_url' => 'https://apjii.or.id',
            ],
            [
                'name' => '.id Academy',
                'category' => 'national',
                'subtitle' => 'Program edukasi PANDI (Pengelola Nama Domain Internet Indonesia)',
                'logo_path' => 'images/partners/id-academy.png',
                'website_url' => 'https://pandi.id',
            ],
            [
                'name' => 'Universitas Esa Unggul',
                'category' => 'school',
                'subtitle' => 'Perguruan tinggi mitra',
                'logo_path' => 'images/partners/esa-unggul.png',
                'website_url' => 'https://www.esaunggul.ac.id',
            ],
        ];
    }
}
