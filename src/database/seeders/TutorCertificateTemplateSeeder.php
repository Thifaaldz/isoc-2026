<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use Illuminate\Database\Seeder;

/**
 * Template sertifikat tutor sebagai narasumber. Aman dijalankan ulang di server:
 * hanya membuat template bila belum ada, tanpa menimpa hasil edit Studio,
 * tanpa mengubah template default, event, maupun sertifikat yang sudah ada.
 */
class TutorCertificateTemplateSeeder extends Seeder
{
    private const NARRATIVE = "Atas dedikasi dan kontribusinya sebagai\nNARASUMBER\npada kegiatan\n{{event_title}}\nyang diselenggarakan pada {{event_date}}";

    public function run(): void
    {
        CertificateTemplate::query()->firstOrCreate(
            ['name' => CertificateTemplate::TUTOR_TEMPLATE_NAME],
            [
                'learning_event_id' => null,
                'orientation' => 'landscape',
                'width_mm' => 297,
                'height_mm' => 210,
                'background_color' => '#ffffff',
                'background_image' => CertificateTemplateSeeder::BACKGROUND,
                'is_default' => false,
                'elements' => $this->elements(),
            ],
        );
    }

    /** Tata letak sama dengan sertifikat peserta; hanya label nama dan narasinya yang berbeda. */
    private function elements(): array
    {
        return collect(CertificateTemplateSeeder::elements())
            ->map(fn (array $element): array => match ($element['label']) {
                'Nama peserta' => [...$element, 'label' => 'Nama narasumber'],
                'Narasi kegiatan' => [...$element, 'content' => self::NARRATIVE],
                default => $element,
            })
            ->all();
    }
}
