<x-filament-panels::page>
    @include('filament.event-overview.styles')
    @php
        $events = $this->events;
        $event = $this->selectedEvent;
        $overview = $this->overview();
    @endphp

    <div class="eo">
        @if (! $event)
            <div class="eo-card eo-pad"><p class="eo-muted">Belum ada event.</p></div>
        @else
            @php
                $status = $overview->finalReportStatus();
                $checklist = $overview->finalReportChecklist();
                $doneCount = collect($checklist)->where('done', true)->count();
                $ready = $doneCount === count($checklist);
                $termTwo = $overview->termTwoChecklist();
            @endphp

            <div class="eo-card eo-pad" style="display: grid; gap: 14px;">
                <div class="eo-row eo-between" style="align-items: flex-start;">
                    <div style="display: grid; gap: 6px;">
                        <div class="eo-row">
                            <span class="eo-pill {{ $status['color'] }}">{{ $status['label'] }}</span>
                            <span class="eo-pill {{ $ready ? 'success' : 'gray' }}">{{ $doneCount }}/{{ count($checklist) }} syarat lengkap</span>
                        </div>
                        <p class="eo-title">{{ $event->title }}</p>
                        <span class="eo-meta"><x-heroicon-o-calendar-days /> {{ $overview->schedule() }}</span>
                    </div>
                    @if ($events->count() > 1)
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="selectedEventId" aria-label="Pilih event">
                                @foreach ($events as $option)
                                    <option value="{{ $option->id }}">{{ $option->title }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    @endif
                </div>

                <div class="eo-bar" style="height: 10px;"><span style="background: var(--eo-success); width: {{ (int) round($doneCount / max(1, count($checklist)) * 100) }}%;"></span></div>

                @if ($event->final_report_status === 'revision' && $event->final_report_notes)
                    <div class="eo-card eo-pad eo-next warning" style="padding: 12px 14px;">
                        <p class="eo-h3" style="margin-bottom: 4px;">Catatan revisi dari RTIK Pusat</p>
                        <p class="eo-muted">{{ $event->final_report_notes }}</p>
                    </div>
                @endif

                <div class="eo-row">
                    <a class="eo-btn" href="{{ route('reports.events.activity.preview', $event) }}" target="_blank" rel="noopener"><x-heroicon-o-eye /> Preview Laporan</a>
                    <a class="eo-btn" href="{{ route('reports.events.activity.download', $event) }}"><x-heroicon-o-arrow-down-tray /> Unduh PDF</a>
                    <a class="eo-btn" href="{{ $this->evidenceUrl() }}"><x-heroicon-o-photo /> Kelola Bukti Dukung</a>
                    @if ($this->canSubmit())
                        <button type="button" class="eo-btn primary" wire:click="submit" wire:confirm="Kirim laporan final ke Admin RTIK Pusat untuk approval Termin-2?" @disabled(! $ready)
                            @if (! $ready) style="opacity: .5; cursor: not-allowed;" title="Lengkapi semua syarat terlebih dahulu" @endif>
                            <x-heroicon-o-paper-airplane /> Submit Laporan Final
                        </button>
                    @endif
                </div>
                <p class="eo-muted">
                    @if ($event->final_report_status === 'submitted')
                        Laporan sudah dikirim {{ $event->final_report_submitted_at?->translatedFormat('d F Y H:i') }}. Tunggu approval RTIK Pusat.
                    @elseif ($event->final_report_status === 'approved')
                        Laporan disetujui {{ $event->final_report_approved_at?->translatedFormat('d F Y') }}. Termin-2 dicairkan.
                    @elseif (! $ready)
                        Lengkapi syarat yang masih kosong di bawah ({{ count($checklist) - $doneCount }} lagi), lalu tekan "Submit Laporan Final".
                    @else
                        Semua syarat lengkap. Tekan "Submit Laporan Final".
                    @endif
                </p>
            </div>

            <div style="display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(min(100%, 440px), 1fr)); align-items: start;">
                <div class="eo-card eo-pad">
                    <p class="eo-h3">Syarat laporan final</p>
                    <div style="display: grid; gap: 4px;">
                        @foreach ($checklist as $item)
                            @include('filament.pages.partials.final-report-item', ['item' => $item])
                        @endforeach
                    </div>
                </div>

                <div class="eo-card eo-pad">
                    <p class="eo-h3">Checklist Termin-2</p>
                    <p class="eo-muted" style="font-size: 12px; margin-bottom: 10px;">{{ \App\Support\TorEventTemplate::TERM_NOTES[2] }} Tercentang otomatis dari data event; bukti yang diupload tercentang setelah disetujui Pusat.</p>
                    <div style="display: grid; gap: 4px;">
                        @foreach ($termTwo as $item)
                            @include('filament.pages.partials.final-report-item', ['item' => $item])
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    <x-filament::modal id="system-proof-preview" width="5xl" :heading="$this->proofPreviewTitle">
        @if ($this->proofPreviewUrl)
            <iframe src="{{ $this->proofPreviewUrl }}" title="{{ $this->proofPreviewTitle }}" style="border: 0; border-radius: 8px; height: 75vh; width: 100%;"></iframe>
            <x-slot name="footerActions">
                <x-filament::button tag="a" :href="$this->proofPreviewUrl" target="_blank" color="gray" icon="heroicon-o-arrow-top-right-on-square">Buka di tab baru</x-filament::button>
            </x-slot>
        @endif
    </x-filament::modal>
</x-filament-panels::page>
