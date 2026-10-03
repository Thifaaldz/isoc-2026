<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use Illuminate\Database\Seeder;

class CertificateTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = CertificateTemplate::updateOrCreate(
            ['name' => 'Template Sertifikat Sena - Landscape'],
            [
                'learning_event_id' => null,
                'orientation' => 'landscape',
                'width_mm' => 297,
                'height_mm' => 210,
                'background_color' => '#ffffff',
                'is_default' => true,
                'elements' => $this->certificateTemplateElements(),
            ],
        );

        Certificate::query()
            ->whereNull('certificate_template_id')
            ->update(['certificate_template_id' => $template->id]);

        \App\Models\LearningEvent::query()
            ->whereNull('certificate_template_id')
            ->update(['certificate_template_id' => $template->id]);
    }

    private function certificateTemplateElements(): array
    {
        return [
            ['type' => 'white_box', 'label' => 'Penutup logo template lama', 'x' => 0, 'y' => 0, 'width' => 190, 'height' => 38, 'color' => '#ffffff'],
            ['type' => 'event_partner_logos', 'label' => 'Logo Mitra Event', 'x' => 12, 'y' => 8, 'width' => 175, 'height' => 24],
            ['type' => 'sena_logo', 'label' => 'Logo Sena', 'image_path' => '/images/sena-logo.png', 'x' => 230, 'y' => 13, 'width' => 45, 'height' => 18],
            ['type' => 'white_box', 'label' => 'Penutup nomor sertifikat', 'x' => 65, 'y' => 56.8, 'width' => 167, 'height' => 12, 'color' => '#ffffff'],
            ['type' => 'custom_text', 'label' => 'Nomor sertifikat', 'content' => 'No. {{certificate_number}}', 'x' => 70, 'y' => 57.8, 'width' => 157, 'height' => 9, 'font_size' => 14, 'font_weight' => '500', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'white_box', 'label' => 'Penutup label penerima', 'x' => 66, 'y' => 69, 'width' => 165, 'height' => 8, 'color' => '#ffffff'],
            ['type' => 'custom_text', 'label' => 'Diberikan kepada', 'content' => 'diberikan kepada:', 'x' => 70, 'y' => 70.2, 'width' => 157, 'height' => 8, 'font_size' => 18, 'font_weight' => '400', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'white_box', 'label' => 'Penutup nama peserta', 'x' => 44, 'y' => 78.2, 'width' => 209, 'height' => 18, 'color' => '#ffffff'],
            ['type' => 'participant_name', 'label' => 'Nama peserta', 'x' => 48, 'y' => 80, 'width' => 201, 'height' => 15, 'font_size' => 26, 'font_weight' => '800', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'white_box', 'label' => 'Penutup narasi kegiatan', 'x' => 0, 'y' => 96.5, 'width' => 297, 'height' => 43, 'color' => '#ffffff'],
            ['type' => 'custom_text', 'label' => 'Narasi kegiatan', 'content' => "Atas dedikasi, integritas, dan keberhasilan menyelesaikan seluruh rangkaian pelatihan intensif\n{{event_title}}\nyang diselenggarakan pada {{event_date}}, serta dinyatakan kompeten sebagai:\n{{competency_title}}", 'x' => 14, 'y' => 98.8, 'width' => 269, 'height' => 40, 'font_size' => 13.5, 'font_weight' => '700', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'white_box', 'label' => 'Penutup tanda tangan template lama', 'x' => 0, 'y' => 140, 'width' => 297, 'height' => 70, 'color' => '#ffffff'],
            ['type' => 'signature_image', 'label' => 'Upload TTD Penyelenggara', 'image_path' => null, 'x' => 45, 'y' => 146, 'width' => 52, 'height' => 18],
            ['type' => 'custom_text', 'label' => 'Nama TTD Penyelenggara', 'content' => "{{organizer_name}}\nPenyelenggara", 'x' => 30, 'y' => 166, 'width' => 95, 'height' => 18, 'font_size' => 10, 'font_weight' => '700', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'signature_image', 'label' => 'Upload TTD Tutor / Mitra', 'image_path' => null, 'x' => 198, 'y' => 146, 'width' => 52, 'height' => 18],
            ['type' => 'custom_text', 'label' => 'Nama TTD Tutor / Mitra', 'content' => "{{tutor_name}}\n{{tutor_institution}}", 'x' => 172, 'y' => 166, 'width' => 95, 'height' => 18, 'font_size' => 10, 'font_weight' => '700', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'qr_code', 'label' => 'QR Verifikasi', 'x' => 262, 'y' => 176, 'width' => 26, 'height' => 26],
        ];
    }
}
