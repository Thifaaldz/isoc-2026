<?php

namespace App\Filament\Support;

/**
 * Whitelist tipe file upload. Gambar sengaja tidak memakai image/* agar SVG (bisa berisi skrip) ditolak.
 */
class UploadTypes
{
    public const IMAGES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public const PDF = ['application/pdf'];

    public const OFFICE = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    public const VIDEOS = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];

    /** Dokumen kegiatan: PDF, Office, dan gambar hasil scan/foto. */
    public static function documents(): array
    {
        return [...self::PDF, ...self::OFFICE, ...self::IMAGES];
    }

    /** Bukti dukung: foto, PDF/dokumen, dan video. */
    public static function evidence(): array
    {
        return [...self::documents(), ...self::VIDEOS];
    }
}
