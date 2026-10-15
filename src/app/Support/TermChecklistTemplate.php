<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Once;
use Illuminate\Support\Str;

/**
 * Template checklist termin (Termin-1 & Termin-2) di wizard event. Template "Standar TOR" adalah checklist TOR bawaan
 * (TorEventTemplate::TERM_CHECKLIST); template lain disimpan di site_settings (tanpa tabel baru).
 * Item template yang dipilih tersalin ke kolom budget_items event (key, term, description, done, template),
 * jadi event yang sudah berjalan tidak berubah saat template diubah.
 */
class TermChecklistTemplate
{
    public const DEFAULT_KEY = 'standar-tor';

    public const SETTING_KEY = 'term_checklist_templates';

    public const TERMS = [1, 2];

    /** @return array<string, array{name: string, terms: array<int, array<string, string>>}> */
    public static function all(): array
    {
        return once(function (): array {
            $stored = Schema::hasTable('site_settings') ? (array) (SiteSetting::getValue(self::SETTING_KEY) ?? []) : [];

            return [self::DEFAULT_KEY => ['name' => 'Standar TOR ISOC', 'terms' => TorEventTemplate::TERM_CHECKLIST]]
                + array_filter($stored, fn ($template, $key) => $key !== self::DEFAULT_KEY && is_array($template) && filled($template['name'] ?? null), ARRAY_FILTER_USE_BOTH);
        });
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::all())->map(fn (array $template) => $template['name'])->all();
    }

    /** @return array<int, array<string, string>> Item per termin dari template. */
    public static function termsOf(?string $key): array
    {
        $terms = self::all()[$key ?? '']['terms'] ?? TorEventTemplate::TERM_CHECKLIST;

        return collect(self::TERMS)->mapWithKeys(fn (int $term) => [$term => (array) ($terms[$term] ?? [])])->all();
    }

    /**
     * Item per termin yang dipakai event, dibaca dari salinan di budget_items.
     * Event lama (budget_items kosong / RAB lama tanpa key) memakai checklist standar TOR.
     *
     * @return array<int, array<string, string>>
     */
    public static function terms(?array $budgetItems): array
    {
        $items = collect($budgetItems ?? [])->filter(fn ($item) => is_array($item) && filled($item['key'] ?? null) && in_array((int) ($item['term'] ?? 0), self::TERMS, true));

        if ($items->isEmpty()) {
            return self::termsOf(self::DEFAULT_KEY);
        }

        return collect(self::TERMS)->mapWithKeys(fn (int $term) => [$term => $items
            ->filter(fn (array $item) => (int) $item['term'] === $term)
            ->mapWithKeys(fn (array $item) => [(string) $item['key'] => (string) ($item['description'] ?? $item['key'])])
            ->all()])->all();
    }

    public static function templateKey(?array $budgetItems): string
    {
        $item = collect($budgetItems ?? [])->first(fn ($item) => is_array($item) && filled($item['template'] ?? null));

        return $item['template'] ?? self::DEFAULT_KEY;
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
            'terms' => collect(self::TERMS)->mapWithKeys(fn (int $term) => [$term => self::prepareItems((array) ($data["term_{$term}"] ?? []))])->all(),
            'created_by' => auth()->id(),
        ];

        SiteSetting::putValue(self::SETTING_KEY, $stored);
        Once::flush();

        return $key;
    }

    /** @return array<int, array{key: string, label: string}> Item template untuk isi awal form template baru. */
    public static function formItems(int $term): array
    {
        return collect(TorEventTemplate::TERM_CHECKLIST[$term] ?? [])
            ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * Item standar mempertahankan key-nya (Termin-2 standar tetap dicentang otomatis dari data event);
     * item baru mendapat key dari labelnya.
     *
     * @return array<string, string>
     */
    private static function prepareItems(array $items): array
    {
        $prepared = [];

        foreach (array_values($items) as $item) {
            $label = trim((string) ($item['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $key = (string) ($item['key'] ?? '');

            if ($key === '' || isset($prepared[$key])) {
                $base = Str::limit('k_' . Str::slug($label, '_'), 40, '');
                $key = $base;

                for ($i = 2; isset($prepared[$key]); $i++) {
                    $key = "{$base}_{$i}";
                }
            }

            $prepared[$key] = $label;
        }

        return $prepared;
    }
}
