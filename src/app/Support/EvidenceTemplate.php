<?php

namespace App\Support;

use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Once;
use Illuminate\Support\Str;

/**
 * Template bukti dukung event. Template "Standar TOR" (foto wajib a-g + video slogan) bawaan sistem;
 * template lain dibuat dari wizard event dan disimpan di site_settings (tanpa tabel baru).
 * Item template yang dipilih disalin ke kolom learning_events.evidence_checklist, jadi event yang sudah berjalan
 * tidak berubah saat template diubah. Event tanpa evidence_checklist memakai template standar.
 */
class EvidenceTemplate
{
    public const DEFAULT_KEY = 'standar-tor';

    public const SETTING_KEY = 'evidence_templates';

    /** Kolom evidences.type maksimal 30 karakter. */
    private const TYPE_MAX = 30;

    /** @return array<string, array{name: string, items: array<int, array<string, mixed>>}> */
    public static function all(): array
    {
        return once(function (): array {
            $stored = Schema::hasTable('site_settings') ? (array) (SiteSetting::getValue(self::SETTING_KEY) ?? []) : [];

            return [self::DEFAULT_KEY => ['name' => 'Standar TOR ISOC (foto wajib a-g + video slogan)', 'items' => self::defaultItems()]]
                + array_filter($stored, fn ($template, $key) => $key !== self::DEFAULT_KEY && is_array($template) && filled($template['name'] ?? null), ARRAY_FILTER_USE_BOTH);
        });
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::all())->map(fn (array $template) => $template['name'])->all();
    }

    /** @return array<int, array<string, mixed>> */
    public static function defaultItems(): array
    {
        return [
            ...collect(Evidence::REQUIRED_PHOTOS)->map(fn (array $photo, string $type) => [
                'type' => $type,
                'title' => $photo['title'],
                'min' => $photo['min'],
                'instruction' => $photo['instruction'],
                'photo' => true,
            ])->values()->all(),
            [
                'type' => 'video_slogan',
                'title' => 'Video slogan',
                'min' => 1,
                'instruction' => 'Video Tutor & Peserta dengan slogan "ISOC - The Internet is for Everyone".',
                'photo' => false,
            ],
        ];
    }

    /** Simpan template baru dari wizard; mengembalikan key template. */
    public static function create(array $data): string
    {
        $stored = Schema::hasTable('site_settings') ? (array) (SiteSetting::getValue(self::SETTING_KEY) ?? []) : [];
        $base = Str::slug((string) $data['name']) ?: 'template';
        $key = $base;

        for ($i = 2; $key === self::DEFAULT_KEY || isset($stored[$key]); $i++) {
            $key = "{$base}-{$i}";
        }

        $stored[$key] = [
            'name' => (string) $data['name'],
            'items' => self::prepareItems((array) ($data['items'] ?? [])),
            'created_by' => auth()->id(),
        ];

        SiteSetting::putValue(self::SETTING_KEY, $stored);
        Once::flush();

        return $key;
    }

    /** Isi kolom evidence_checklist untuk template terpilih. Template standar cukup disimpan key-nya. */
    public static function snapshot(?string $key): ?array
    {
        $template = self::all()[$key ?? ''] ?? null;

        if (! $template || $key === self::DEFAULT_KEY) {
            return ['template' => self::DEFAULT_KEY];
        }

        return ['template' => $key, 'name' => $template['name'], 'items' => array_values($template['items'])];
    }

    /**
     * Item bukti dukung event, di-key per jenis bukti (evidences.type).
     * Foto mendapat urutan huruf (a, b, c, ...) seperti foto wajib TOR.
     *
     * @return array<string, array{type: string, title: string, label: string, min: int, instruction: string, photo: bool, step: ?string}>
     */
    public static function itemsFor(?LearningEvent $event): array
    {
        $checklist = is_array($event?->evidence_checklist) ? $event->evidence_checklist : [];
        $items = is_array($checklist['items'] ?? null)
            ? $checklist['items']
            : (self::all()[$checklist['template'] ?? '']['items'] ?? self::defaultItems());

        $normalized = [];
        $step = 0;

        foreach ($items as $item) {
            $type = (string) ($item['type'] ?? '');

            if ($type === '' || isset($normalized[$type])) {
                continue;
            }

            $photo = (bool) ($item['photo'] ?? false);
            $letter = $photo ? chr(97 + $step++) : null;
            $title = (string) ($item['title'] ?? $type);

            $normalized[$type] = [
                'type' => $type,
                'title' => $title,
                'label' => $photo ? "Foto {$letter}. {$title}" : $title,
                'min' => max(1, (int) ($item['min'] ?? 1)),
                'instruction' => (string) ($item['instruction'] ?? ''),
                'photo' => $photo,
                'step' => $letter,
            ];
        }

        return $normalized;
    }

    public static function itemsForEventId(?int $eventId): array
    {
        return once(fn () => self::itemsFor($eventId ? LearningEvent::query()->find($eventId, ['id', 'evidence_checklist']) : null));
    }

    /** Nama template yang dipakai event. */
    public static function nameFor(?LearningEvent $event): string
    {
        $checklist = is_array($event?->evidence_checklist) ? $event->evidence_checklist : [];
        $key = $checklist['template'] ?? self::DEFAULT_KEY;

        return $checklist['name'] ?? self::all()[$key]['name'] ?? self::all()[self::DEFAULT_KEY]['name'];
    }

    /** Label jenis bukti custom dari semua template (jenis standar memakai Evidence::TYPES). */
    public static function customLabels(): array
    {
        return once(fn () => collect(self::all())
            ->flatMap(fn (array $template) => $template['items'])
            ->filter(fn ($item) => is_array($item) && ! isset(Evidence::TYPES[$item['type'] ?? '']))
            ->mapWithKeys(fn (array $item) => [(string) $item['type'] => (string) ($item['title'] ?? $item['type'])])
            ->all());
    }

    /**
     * Item dari form: jenis standar dipertahankan, item baru mendapat key "bd_<judul>" (maks 30 karakter, unik).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function prepareItems(array $items): array
    {
        $used = [];
        $prepared = [];

        foreach (array_values($items) as $item) {
            $title = trim((string) ($item['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $type = (string) ($item['type'] ?? '');

            if ($type === '' || isset($used[$type])) {
                $base = Str::limit('bd_' . Str::slug($title, '_'), self::TYPE_MAX - 3, '');
                $type = $base;

                for ($i = 2; isset($used[$type]) || isset(Evidence::TYPES[$type]); $i++) {
                    $type = "{$base}_{$i}";
                }
            }

            $used[$type] = true;
            $prepared[] = [
                'type' => $type,
                'title' => $title,
                'min' => max(1, (int) ($item['min'] ?? 1)),
                'instruction' => trim((string) ($item['instruction'] ?? '')),
                'photo' => (bool) ($item['photo'] ?? false),
            ];
        }

        return $prepared;
    }
}
