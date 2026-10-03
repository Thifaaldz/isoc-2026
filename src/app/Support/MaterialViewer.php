<?php

namespace App\Support;

use App\Models\LearningMaterial;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Menentukan cara menampilkan materi (PDF, PPT, video file, YouTube, link) di semua panel. */
class MaterialViewer
{
    /** @return array{type: string, url: ?string, embed_url: ?string} */
    public static function for(?LearningMaterial $material): array
    {
        if (! $material) {
            return ['type' => 'empty', 'url' => null, 'embed_url' => null];
        }

        $url = static::url($material);
        $embedUrl = $url;

        if ($url && $youtube = static::youtubeEmbedUrl($url)) {
            $embedUrl = $youtube;
        } elseif ($url && $material->type === 'ppt') {
            // PPT ditampilkan dari versi PDF-nya (hasil konversi) agar bisa dipreview di semua lingkungan;
            // tanpa versi PDF, gunakan Office Online (butuh file yang bisa diakses publik).
            $embedUrl = static::pdfPreviewUrl($material)
                ?? 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode(url($url));
        }

        return ['type' => (string) $material->type, 'url' => $url, 'embed_url' => $embedUrl];
    }

    /** URL versi PDF dari file PPT/PPTX (nama sama, ekstensi .pdf) bila tersedia di disk public. */
    public static function pdfPreviewUrl(LearningMaterial $material): ?string
    {
        if (! $material->file_path || ! preg_match('/\.pptx?$/i', $material->file_path)) {
            return null;
        }

        $pdf = preg_replace('/\.pptx?$/i', '.pdf', $material->file_path);

        return Storage::disk('public')->exists($pdf) ? Storage::disk('public')->url($pdf) : null;
    }

    public static function url(LearningMaterial $material): ?string
    {
        if ($material->external_url) {
            return $material->external_url;
        }

        return $material->file_path ? Storage::disk('public')->url($material->file_path) : null;
    }

    public static function youtubeEmbedUrl(string $url): ?string
    {
        if (! Str::contains($url, ['youtube.com', 'youtu.be'])) {
            return null;
        }

        $parts = parse_url($url);
        $videoId = null;

        if (($parts['host'] ?? '') === 'youtu.be') {
            $videoId = trim($parts['path'] ?? '', '/');
        }

        if (! $videoId && isset($parts['query'])) {
            parse_str($parts['query'], $query);
            $videoId = $query['v'] ?? null;
        }

        if (! $videoId && str_contains($parts['path'] ?? '', '/embed/')) {
            $videoId = basename($parts['path']);
        }

        return $videoId ? 'https://www.youtube.com/embed/' . $videoId : null;
    }
}
