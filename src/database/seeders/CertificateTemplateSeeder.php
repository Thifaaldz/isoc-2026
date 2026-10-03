<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use Illuminate\Database\Seeder;

class CertificateTemplateSeeder extends Seeder
{
    /** Background eSertifikat Litdig 2026 yang hanya menyisakan judul SERTIFIKAT. */
    private const BACKGROUND = '/certificate-templates/esertifikat-litdig-2026-bersih.png';

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
                'background_image' => self::BACKGROUND,
                'is_default' => true,
                'elements' => $this->certificateTemplateElements(),
            ],
        );

        // Sena satu-satunya template default.
        CertificateTemplate::query()->whereKeyNot($template->id)->update(['is_default' => false]);

        Certificate::query()
            ->whereNull('certificate_template_id')
            ->update(['certificate_template_id' => $template->id]);

        \App\Models\LearningEvent::query()
            ->whereNull('certificate_template_id')
            ->update(['certificate_template_id' => $template->id]);
    }

    /** Desain mengikuti docs/templte sertifikat/eSertifikat Litdig 2026.pdf, dengan TTD Ketua Umum ISOC Indonesia dan Relawan TIK Indonesia. */
    private function certificateTemplateElements(): array
    {
        return [
            ['type' => 'event_partner_logos', 'label' => 'Logo Mitra Event', 'x' => 44.7, 'y' => 9, 'width' => 207.6, 'height' => 24],
            ['type' => 'custom_text', 'label' => 'Nomor sertifikat', 'content' => 'No. {{certificate_number}}', 'x' => 70, 'y' => 58, 'width' => 157, 'height' => 8, 'font_size' => 12, 'font_weight' => '400', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'custom_text', 'label' => 'Diberikan kepada', 'content' => 'diberikan kepada:', 'x' => 70, 'y' => 68, 'width' => 157, 'height' => 9, 'font_size' => 18, 'font_weight' => '400', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'participant_name', 'label' => 'Nama peserta', 'x' => 38, 'y' => 78, 'width' => 221, 'height' => 14, 'font_size' => 26, 'font_weight' => '800', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'custom_text', 'label' => 'Narasi kegiatan', 'content' => "Atas dedikasi, integritas, dan keberhasilan menyelesaikan seluruh rangkaian pelatihan intensif\n{{event_title}}\nyang diselenggarakan pada {{event_date}}, serta dinyatakan kompeten sebagai:\n{{competency_title}}", 'x' => 14, 'y' => 96, 'width' => 269, 'height' => 38, 'font_size' => 13, 'font_weight' => '700', 'align' => 'center', 'color' => '#202427'],

            ['type' => 'custom_text', 'label' => 'Lembaga TTD kiri', 'content' => 'Internet Society Indonesia', 'x' => 36, 'y' => 141, 'width' => 80, 'height' => 7, 'font_size' => 12, 'font_weight' => '700', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'signature_image', 'label' => 'TTD Ketua Umum ISOC Indonesia', 'image_path' => '/images/signatures/tinuk-andriyanti.png', 'x' => 49, 'y' => 150, 'width' => 54, 'height' => 22.5],
            ['type' => 'white_box', 'label' => 'Garis TTD kiri', 'x' => 46, 'y' => 176, 'width' => 60, 'height' => 0.4, 'color' => '#202427'],
            ['type' => 'custom_text', 'label' => 'Nama TTD kiri', 'content' => "Tinuk Andriyanti\nKetua Umum", 'x' => 36, 'y' => 178, 'width' => 80, 'height' => 16, 'font_size' => 13, 'font_weight' => '700', 'align' => 'center', 'color' => '#202427'],

            ['type' => 'custom_text', 'label' => 'Lembaga TTD kanan', 'content' => 'Relawan TIK Indonesia', 'x' => 196, 'y' => 141, 'width' => 80, 'height' => 7, 'font_size' => 12, 'font_weight' => '700', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'signature_image', 'label' => 'TTD Ketua Umum Relawan TIK Indonesia', 'image_path' => '/images/signatures/hani-purnawanti.png', 'x' => 216, 'y' => 151, 'width' => 40, 'height' => 21.6],
            ['type' => 'white_box', 'label' => 'Garis TTD kanan', 'x' => 206, 'y' => 176, 'width' => 60, 'height' => 0.4, 'color' => '#202427'],
            ['type' => 'custom_text', 'label' => 'Nama TTD kanan', 'content' => "Hani Purnawanti\nKetua Umum", 'x' => 196, 'y' => 178, 'width' => 80, 'height' => 16, 'font_size' => 13, 'font_weight' => '700', 'align' => 'center', 'color' => '#202427'],

            ['type' => 'qr_code', 'label' => 'QR Verifikasi', 'x' => 270, 'y' => 180, 'width' => 22, 'height' => 22],
        ];
    }
}
