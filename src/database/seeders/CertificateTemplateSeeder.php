<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\LearningEvent;
use Illuminate\Database\Seeder;

class CertificateTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $event = LearningEvent::query()->orderBy('id')->first();

        $template = CertificateTemplate::updateOrCreate(
            ['name' => 'Template Sertifikat ISOC - Landscape'],
            [
                'learning_event_id' => $event?->id,
                'orientation' => 'landscape',
                'width_mm' => 297,
                'height_mm' => 210,
                'background_color' => '#ffffff',
                'is_default' => true,
                'elements' => [
                    ['type' => 'custom_text', 'label' => 'Judul kecil', 'content' => 'ISOC Webinar Certificate', 'x' => 30, 'y' => 22, 'width' => 237, 'height' => 10, 'font_size' => 14, 'font_weight' => '700', 'align' => 'center', 'color' => '#1d4ed8'],
                    ['type' => 'custom_text', 'label' => 'Judul utama', 'content' => 'Sertifikat Peserta', 'x' => 30, 'y' => 38, 'width' => 237, 'height' => 14, 'font_size' => 28, 'font_weight' => '700', 'align' => 'center', 'color' => '#111827'],
                    ['type' => 'custom_text', 'label' => 'Diberikan kepada', 'content' => 'Diberikan kepada', 'x' => 30, 'y' => 78, 'width' => 237, 'height' => 8, 'font_size' => 12, 'font_weight' => '400', 'align' => 'center', 'color' => '#4b5563'],
                    ['type' => 'participant_name', 'label' => 'Nama peserta', 'x' => 30, 'y' => 90, 'width' => 237, 'height' => 14, 'font_size' => 26, 'font_weight' => '700', 'align' => 'center', 'color' => '#111827'],
                    ['type' => 'custom_text', 'label' => 'Deskripsi', 'content' => 'Atas partisipasinya dalam program {{event_title}} yang diselenggarakan oleh ISOC.', 'x' => 48, 'y' => 113, 'width' => 201, 'height' => 22, 'font_size' => 12, 'font_weight' => '400', 'align' => 'center', 'color' => '#374151'],
                    ['type' => 'custom_text', 'label' => 'TTD kiri', 'content' => "ISOC Indonesia Chapter Jakarta\nPenyelenggara", 'x' => 35, 'y' => 158, 'width' => 85, 'height' => 25, 'font_size' => 10, 'font_weight' => '400', 'align' => 'center', 'color' => '#111827'],
                    ['type' => 'custom_text', 'label' => 'TTD kanan', 'content' => "{{event_title}}\nProgram", 'x' => 177, 'y' => 158, 'width' => 85, 'height' => 25, 'font_size' => 10, 'font_weight' => '400', 'align' => 'center', 'color' => '#111827'],
                    ['type' => 'custom_text', 'label' => 'Footer nomor', 'content' => 'No. Sertifikat: {{certificate_number}} - Diterbitkan: {{issued_date}}', 'x' => 50, 'y' => 190, 'width' => 197, 'height' => 7, 'font_size' => 9, 'font_weight' => '400', 'align' => 'center', 'color' => '#6b7280'],
                    ['type' => 'qr_code', 'label' => 'QR verifikasi', 'x' => 255, 'y' => 172, 'width' => 24, 'height' => 24],
                ],
            ],
        );

        Certificate::query()
            ->whereNull('certificate_template_id')
            ->update(['certificate_template_id' => $template->id]);
    }
}
