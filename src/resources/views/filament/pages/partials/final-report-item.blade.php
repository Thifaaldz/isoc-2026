@php
    // Item absensi & microsite bisa digenerate dari sistem atau diupload manual.
    $kind = $item['proof_kind'] ?? null;
    $locked = $this->reportLocked();
    $systemProof = $kind ? $this->systemProof($kind) : null;
@endphp
<div style="border-bottom: 1px solid var(--eo-border); padding: 6px 0;">
    <div class="eo-row eo-between" style="flex-wrap: nowrap;">
        <div style="display: grid; gap: 1px; min-width: 0;">
            <span class="eo-meta" style="color: {{ $item['done'] ? 'var(--eo-success)' : 'var(--eo-text)' }}; font-weight: 600;">
                @if ($item['done']) <x-heroicon-s-check-circle /> @else <x-heroicon-o-x-circle style="color: var(--eo-danger);" /> @endif
                {{ $item['label'] }}
            </span>
            @if ($item['detail'])
                <span class="eo-muted" style="font-size: 12px; padding-left: 22px;">{{ $item['detail'] }}</span>
            @endif
        </div>
        <div style="flex: 0 0 auto;">
            @if ($kind && $item['mode'] === 'manual')
                {{ ($this->uploadEvidenceAction)(['type' => $item['key']]) }}
            @elseif (! $kind && $item['evidence'])
                {{ ($this->uploadEvidenceAction)(['type' => $item['evidence']]) }}
            @elseif (! $kind && $item['link'])
                <a class="eo-btn" style="padding: 5px 10px; font-size: 12px;" href="{{ $item['link'] }}"><x-heroicon-o-eye /> Lihat</a>
            @endif
        </div>
    </div>

    @if ($kind)
        <div style="display: grid; gap: 8px; padding: 8px 0 2px 22px;">
            <div class="eo-row" style="gap: 6px;">
                @foreach (['system' => ['Generate dari sistem', 'heroicon-o-cpu-chip'], 'manual' => ['Upload manual', 'heroicon-o-arrow-up-tray']] as $mode => [$modeLabel, $modeIcon])
                    <button type="button" class="eo-btn {{ $item['mode'] === $mode ? 'primary' : '' }}" style="padding: 5px 10px; font-size: 12px;"
                        wire:click="setProofMode('{{ $kind }}', '{{ $mode }}')" wire:loading.attr="disabled" @disabled($locked)>
                        <x-dynamic-component :component="$modeIcon" /> {{ $modeLabel }}
                    </button>
                @endforeach
                <span wire:loading wire:target="setProofMode,generateProof" class="eo-muted" style="font-size: 12px;">Membuat PDF...</span>
            </div>

            @if ($item['mode'] === null)
                <p class="eo-muted" style="font-size: 12px;">
                    {{ $kind === 'attendance'
                        ? 'Pilih sumber daftar hadir: dari peserta yang memasukkan kode absensi, atau upload absensi basah (tanda tangan).'
                        : 'Pilih sumber bukti microsite: rekap link microsite yang dikumpulkan peserta, atau upload / link Google Drive.' }}
                </p>
            @elseif ($item['mode'] === 'manual')
                <p class="eo-muted" style="font-size: 12px;">
                    {{ $kind === 'attendance' ? 'Upload scan/foto absensi basah bertanda tangan, atau link Google Drive.' : 'Upload bukti hasil microsite, atau link Google Drive.' }}
                    Tercentang setelah disetujui RTIK Pusat.
                </p>
            @else
                @php
                    $rows = $kind === 'attendance' ? $this->checkedIn : $this->microsites;
                @endphp
                <p class="eo-muted" style="font-size: 12px;">
                    @if ($kind === 'attendance')
                        Peserta yang hadir pada {{ $this->selectedEvent?->starts_at?->translatedFormat('d F Y') }} / sudah memasukkan kode absensi ({{ $rows->count() }} orang).
                    @else
                        Link microsite yang dikumpulkan peserta ({{ $rows->count() }} orang). Link sudah dicek dapat diakses saat disimpan peserta.
                    @endif
                </p>
                <div class="eo-row" style="gap: 6px;">
                    @if ($systemProof)
                        <span class="eo-pill success"><x-heroicon-s-document-check style="height: 14px; width: 14px;" /> PDF tersimpan {{ $systemProof->updated_at?->translatedFormat('d M H:i') }}</span>
                        <button type="button" class="eo-btn" style="padding: 5px 10px; font-size: 12px;" wire:click="previewProof('{{ $kind }}')"><x-heroicon-o-eye /> Preview</button>
                    @endif
                    <button type="button" class="eo-btn" style="padding: 5px 10px; font-size: 12px;" wire:click="generateProof('{{ $kind }}')" wire:loading.attr="disabled" @disabled($locked)>
                        <x-heroicon-o-arrow-path /> {{ $systemProof ? 'Generate ulang' : 'Generate PDF' }}
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>
