<div>
    <div class="bg-indigo-600 text-white">
        <div class="max-w-6xl mx-auto px-4 py-12">
            <h1 class="text-3xl font-bold mb-2">Daftar Webinar ISOC</h1>
            <p class="text-indigo-100">Ikuti webinar terbaru, dapatkan sertifikat &amp; kompetensi JP.</p>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="mb-6 max-w-md">
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Cari webinar..."
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
        </div>

        @if ($webinars->isEmpty())
            <div class="text-center text-gray-500 py-20">Belum ada webinar yang tersedia.</div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($webinars as $webinar)
                    <a href="{{ route('webinars.show', $webinar->slug) }}"
                       class="block bg-white rounded-xl shadow hover:shadow-md transition p-5">
                        <div class="text-xs font-semibold uppercase tracking-wide text-indigo-600 mb-1">
                            {{ $webinar->status === 'ongoing' ? 'Sedang Berlangsung' : 'Segera Dibuka' }}
                        </div>
                        <h2 class="text-lg font-semibold mb-2 line-clamp-2">{{ $webinar->title }}</h2>
                        <p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ $webinar->description }}</p>
                        <div class="text-sm text-gray-600 space-y-1">
                            <div>Tutor: {{ $webinar->tutor?->name ?? '-' }}</div>
                            <div>Jadwal: {{ optional($webinar->start_at)->format('d M Y, H:i') ?? '-' }}</div>
                            <div>Peserta: {{ $webinar->enrollments_count }}{{ $webinar->quota ? '/' . $webinar->quota : '' }}</div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $webinars->links() }}
            </div>
        @endif
    </div>
</div>
