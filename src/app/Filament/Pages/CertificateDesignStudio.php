<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\CertificateTemplate;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;

class CertificateDesignStudio extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Validasi & Sertifikat';

    protected static ?string $navigationLabel = 'Studio Sertifikat';

    protected static ?string $title = 'Studio Sertifikat';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.certificate-design-studio';

    #[Url]
    public ?int $templateId = null;

    public string $newElementType = 'custom_text';

    public array $elements = [];

    public function mount(): void
    {
        $this->templateId ??= CertificateTemplate::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->value('id');

        $this->loadTemplate();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

    public function updatedTemplateId(): void
    {
        $this->loadTemplate();
    }

    public function addElement(): void
    {
        $this->elements[] = $this->elementPreset($this->newElementType);

        Notification::make()
            ->title('Elemen baru ditambahkan')
            ->success()
            ->send();
    }

    public function removeElement(int $index): void
    {
        if (! isset($this->elements[$index])) {
            return;
        }

        unset($this->elements[$index]);
        $this->elements = array_values($this->elements);
    }

    public function updateElementPosition(int $index, float $x, float $y): void
    {
        if (! isset($this->elements[$index])) {
            return;
        }

        $this->elements[$index]['x'] = max(0, round($x, 1));
        $this->elements[$index]['y'] = max(0, round($y, 1));
    }

    public function updateElementGeometry(int $index, float $x, float $y, float $width, float $height): void
    {
        if (! isset($this->elements[$index])) {
            return;
        }

        $this->elements[$index]['x'] = max(0, round($x, 1));
        $this->elements[$index]['y'] = max(0, round($y, 1));
        $this->elements[$index]['width'] = max(5, round($width, 1));
        $this->elements[$index]['height'] = max(5, round($height, 1));
    }

    public function saveDesign(): void
    {
        $template = $this->template();

        if (! $template) {
            return;
        }

        $template->update(['elements' => array_values($this->elements)]);

        Notification::make()
            ->title('Desain sertifikat berhasil disimpan')
            ->success()
            ->send();
    }

    public function templateOptions(): array
    {
        return CertificateTemplate::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function elementTypeOptions(): array
    {
        return [
            'custom_text' => 'Tulisan bebas',
            'participant_name' => 'Dataset: Nama peserta',
            'event_title' => 'Dataset: Nama event',
            'certificate_number' => 'Dataset: Nomor sertifikat',
            'issued_date' => 'Dataset: Tanggal terbit',
            'school_name' => 'Dataset: Nama sekolah',
            'class_name' => 'Dataset: Kelas peserta',
            'tutor_name' => 'Dataset: Nama tutor',
            'tutor_institution' => 'Dataset: Lembaga tutor',
            'organizer_name' => 'Dataset: Nama lembaga',
            'tutor_signature' => 'Tanda tangan: Tutor',
            'organizer_signature' => 'Tanda tangan: Lembaga',
            'signature_line' => 'Garis tanda tangan',
            'logo' => 'Gambar: Logo utama',
            'partner_logo' => 'Gambar: Logo mitra',
            'signature_image' => 'Gambar: Tanda tangan',
            'qr_code' => 'QR verifikasi',
        ];
    }

    public function template(): ?CertificateTemplate
    {
        return $this->templateId ? CertificateTemplate::query()->find($this->templateId) : null;
    }

    public function previewValue(array $element): string
    {
        return match ($element['type'] ?? 'custom_text') {
            'participant_name' => 'Nama Peserta',
            'event_title' => 'Nama Event / Seminar',
            'certificate_number' => 'DSC/2026/0001',
            'issued_date' => now()->format('d F Y'),
            'school_name' => 'Nama Sekolah',
            'class_name' => 'Kelas X',
            'tutor_name' => 'Nama Tutor',
            'tutor_institution' => 'Lembaga Tutor',
            'organizer_name' => 'ISOC Indonesia Chapter Jakarta',
            'signature_line' => '________________________',
            default => strtr($element['content'] ?? 'Tulisan bebas', [
                '{{participant_name}}' => 'Nama Peserta',
                '{{event_title}}' => 'Nama Event / Seminar',
                '{{certificate_number}}' => 'DSC/2026/0001',
                '{{issued_date}}' => now()->format('d F Y'),
                '{{school_name}}' => 'Nama Sekolah',
                '{{class_name}}' => 'Kelas X',
                '{{tutor_name}}' => 'Nama Tutor',
                '{{tutor_institution}}' => 'Lembaga Tutor',
                '{{organizer_name}}' => 'ISOC Indonesia Chapter Jakarta',
            ]),
        };
    }

    public function imageUrl(mixed $path): ?string
    {
        $path = $this->uploadedPath($path);

        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return $path;
        }

        $url = Storage::disk('public')->url($path);
        $absolutePath = Storage::disk('public')->path($path);

        if (file_exists($absolutePath)) {
            $url .= '?v=' . filemtime($absolutePath);
        }

        return $url;
    }

    private function loadTemplate(): void
    {
        $this->elements = $this->template()?->elements ?? [];
    }

    private function elementPreset(string $type): array
    {
        return match ($type) {
            'participant_name' => $this->textElement($type, 'Nama peserta', null, 30, 90, 237, 14, 26, '700'),
            'event_title' => $this->textElement($type, 'Nama event', null, 48, 113, 201, 10, 12, '600'),
            'certificate_number' => $this->textElement($type, 'Nomor sertifikat', null, 50, 190, 95, 7, 9),
            'issued_date' => $this->textElement($type, 'Tanggal terbit', null, 152, 190, 95, 7, 9),
            'school_name' => $this->textElement($type, 'Nama sekolah', null, 65, 128, 167, 8, 10),
            'class_name' => $this->textElement($type, 'Nama kelas', null, 105, 138, 87, 8, 10),
            'tutor_name' => $this->textElement($type, 'Nama tutor', null, 177, 164, 85, 8, 10, '700'),
            'tutor_institution' => $this->textElement($type, 'Lembaga tutor', null, 177, 174, 85, 8, 9),
            'organizer_name' => $this->textElement($type, 'Nama lembaga', null, 35, 164, 85, 8, 10, '700'),
            'tutor_signature' => $this->textElement('custom_text', 'TTD tutor', "________________________\n{{tutor_name}}\n{{tutor_institution}}", 177, 148, 85, 34, 9),
            'organizer_signature' => $this->textElement('custom_text', 'TTD lembaga', "________________________\n{{organizer_name}}\nPenyelenggara", 35, 148, 85, 34, 9),
            'signature_line' => $this->textElement($type, 'Garis tanda tangan', null, 35, 152, 85, 7, 10),
            'logo' => $this->imageElement($type, 'Logo utama', 20, 16, 28, 18),
            'partner_logo' => $this->imageElement($type, 'Logo mitra', 249, 16, 28, 18),
            'signature_image' => $this->imageElement($type, 'Gambar tanda tangan', 35, 135, 85, 22),
            'qr_code' => ['type' => 'qr_code', 'label' => 'QR verifikasi', 'x' => 255, 'y' => 172, 'width' => 24, 'height' => 24],
            default => $this->textElement('custom_text', 'Tulisan baru', 'Tulisan baru', 40, 70, 120, 12, 14),
        };
    }

    private function textElement(string $type, string $label, ?string $content, float $x, float $y, float $width, float $height, int $fontSize, string $fontWeight = '400'): array
    {
        return [
            'type' => $type,
            'label' => $label,
            'content' => $content,
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
            'font_size' => $fontSize,
            'font_weight' => $fontWeight,
            'align' => 'center',
            'color' => '#111827',
        ];
    }

    private function imageElement(string $type, string $label, float $x, float $y, float $width, float $height): array
    {
        return [
            'type' => $type,
            'label' => $label,
            'image_path' => null,
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
        ];
    }

    private function uploadedPath(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->uploadedPath($decoded);
            }

            return $value;
        }

        if (is_array($value)) {
            if (isset($value['path'])) {
                return $this->uploadedPath($value['path']);
            }

            if (isset($value['file'])) {
                return $this->uploadedPath($value['file']);
            }

            $first = collect($value)->first(fn ($item) => filled($item));

            return $this->uploadedPath($first);
        }

        return null;
    }
}
