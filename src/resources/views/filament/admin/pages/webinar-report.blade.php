<x-filament-panels::page>
    {{ $this->form }}

    @if ($this->report)
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament::section>
                <div class="text-sm text-gray-500">Total Pendaftar</div>
                <div class="mt-1 text-2xl font-semibold">{{ $this->report['total_enrollments'] }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">Peserta Approved</div>
                <div class="mt-1 text-2xl font-semibold">{{ $this->report['approved_enrollments'] }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">Rata-rata Kehadiran</div>
                <div class="mt-1 text-2xl font-semibold">{{ $this->report['average_attendance'] }}%</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm text-gray-500">Sertifikat Terbit</div>
                <div class="mt-1 text-2xl font-semibold">{{ $this->report['certificates'] }}</div>
            </x-filament::section>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-filament::section heading="Validasi Peserta">
                <dl class="grid gap-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">WAG Verified</dt>
                        <dd class="font-medium">{{ $this->report['wag_verified'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">IG Verified</dt>
                        <dd class="font-medium">{{ $this->report['ig_verified'] }}</dd>
                    </div>
                </dl>
            </x-filament::section>

            <x-filament::section heading="Nilai Test">
                <dl class="grid gap-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Rata-rata Pretest</dt>
                        <dd class="font-medium">{{ $this->report['average_pretest'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Rata-rata Posttest</dt>
                        <dd class="font-medium">{{ $this->report['average_posttest'] }}</dd>
                    </div>
                </dl>
            </x-filament::section>
        </div>

        <x-filament::section heading="Top Ranking">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="py-2 pr-3">Rank</th>
                            <th class="py-2 pr-3">Student</th>
                            <th class="py-2 pr-3">Pretest</th>
                            <th class="py-2 pr-3">Posttest</th>
                            <th class="py-2 pr-3">Kehadiran</th>
                            <th class="py-2 pr-3">Skor Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->report['top_rankings'] as $ranking)
                            <tr class="border-b">
                                <td class="py-2 pr-3">{{ $ranking->rank }}</td>
                                <td class="py-2 pr-3">{{ $ranking->student?->name ?? '-' }}</td>
                                <td class="py-2 pr-3">{{ $ranking->pretest_score }}</td>
                                <td class="py-2 pr-3">{{ $ranking->posttest_score }}</td>
                                <td class="py-2 pr-3">{{ $ranking->attendance_score }}%</td>
                                <td class="py-2 pr-3 font-medium">{{ $ranking->final_score }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-gray-500">Ranking belum dihitung.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="text-sm text-gray-500">Pilih webinar untuk melihat laporan.</div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
