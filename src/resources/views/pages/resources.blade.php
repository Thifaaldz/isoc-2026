@extends('layouts.app')

@section('title', 'Sumber Daya - ISOC Indonesia Jakarta Chapter')

@section('content')
<div class="pt-12 pb-20 px-8 max-w-7xl mx-auto">
    <!-- Hero Section -->
    <header class="mb-16">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-8">
            <div class="max-w-2xl">
                <span class="inline-block bg-tertiary-fixed text-on-tertiary-fixed px-4 py-1 rounded-full text-xs font-bold uppercase tracking-widest mb-4">Sumber Daya</span>
                <h1 class="text-5xl md:text-7xl font-headline font-extrabold text-primary tracking-tighter leading-tight mb-6">
                    Pusat Pengetahuan<br/><span class="text-secondary">ISOC Indonesia.</span>
                </h1>
                <p class="text-lg text-on-surface-variant leading-relaxed max-w-xl">
                    Akses publikasi resmi ISOC: Action Plan, Impact Report, Internet Impact Brief, Policy Brief, dan dokumen strategi untuk mendukung internet yang lebih baik.
                </p>
            </div>
            <div class="w-full md:w-96">
                <div class="relative">
                    <input class="w-full bg-surface-container-highest border-none rounded-xl py-4 pl-12 pr-4 focus:ring-2 focus:ring-secondary transition-all font-body" placeholder="Cari sumber daya..." type="text"/>
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Category Filters -->
    <div class="flex flex-wrap gap-4 mb-12 border-b border-outline-variant/20 pb-8">
        <button class="px-6 py-2 rounded-full bg-primary text-on-primary font-semibold text-sm transition-all">Semua Kategori</button>
        <button class="px-6 py-2 rounded-full bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high font-semibold text-sm transition-all border border-transparent hover:border-outline-variant/30">Action Plan</button>
        <button class="px-6 py-2 rounded-full bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high font-semibold text-sm transition-all border border-transparent hover:border-outline-variant/30">Impact Report</button>
        <button class="px-6 py-2 rounded-full bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high font-semibold text-sm transition-all border border-transparent hover:border-outline-variant/30">Internet Impact Brief</button>
        <button class="px-6 py-2 rounded-full bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high font-semibold text-sm transition-all border border-transparent hover:border-outline-variant/30">Policy Brief</button>
    </div>

    <!-- Bento Grid Layout -->
    <div class="bento-grid">
        <!-- Featured Content -->
        <div class="col-span-12 lg:col-span-8 group">
            <div class="relative h-[500px] rounded-xl overflow-hidden bg-primary-container">
                <img class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-105 transition-transform duration-700" alt="Digital workspace" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA-4A5LIfgFP_vHr7IR_vpgw29Ed6MgOT8gyY0WCD9eRFU_izGHuY_gBr7iTf8hAjjgzTnwuVM2l6XIb1d1PnL1xvsv3EHMvQjWwfNJ0y184NCukzVYx2dL-t-mRYnTUFbu3fft4IhTfYDRoSf6cvLFO-uG6ye2Vzmz8SXb_0tpCxNaFQem2yBo17ZQpAZPZEdCMk5dnKIEfT3jCdJ8Iw9vPIjKSFsN5PoRirhUexNTGtWGiDcQh4RFm0el1b52Z1HFkw5WfzVTO5o"/>
                <div class="absolute inset-0 bg-gradient-to-t from-primary via-transparent to-transparent"></div>
                <div class="absolute bottom-0 left-0 p-10 max-w-2xl">
                    <div class="flex gap-3 mb-4">
                        <span class="bg-secondary text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-tighter">Terbaru</span>
                        <span class="text-white/80 text-xs font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">calendar_today</span>
                            Oktober 2024
                        </span>
                    </div>
                    <h2 class="text-3xl md:text-4xl font-headline font-bold text-white mb-4 leading-tight">ISOC 2025 Action Plan: Connecting the Unconnected</h2>
                    <p class="text-white/70 mb-6 text-lg">Rencana aksi tahunan ISOC untuk memperluas konektivitas internet dan memperkuat komunitas di seluruh dunia, termasuk Indonesia.</p>
                    <button class="flex items-center gap-2 bg-white text-primary px-8 py-3 rounded-lg font-bold hover:bg-secondary-container hover:text-on-secondary-container transition-all">
                        Baca Action Plan
                        <span class="material-symbols-outlined">arrow_right_alt</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Opinion Article -->
        <div class="col-span-12 md:col-span-6 lg:col-span-4 flex flex-col">
            <div class="flex-1 rounded-xl p-8 bg-surface-container-lowest border border-outline-variant/10 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-12">
                    <span class="text-secondary font-bold text-xs uppercase tracking-widest">Internet Impact Brief</span>
                    <span class="material-symbols-outlined text-on-surface-variant/40" style="font-variation-settings: 'FILL' 1;">format_quote</span>
                </div>
                <h3 class="text-2xl font-headline font-bold text-primary mb-4 leading-snug italic">"Dampak Enkripsi End-to-End terhadap Keamanan Data dan Privasi Pengguna Internet"</h3>
                <div class="flex items-center gap-4 mt-auto pt-8">
                    <div class="w-12 h-12 rounded-full bg-surface-variant overflow-hidden">
                        <img class="w-full h-full object-cover" alt="Dr. Andi Wijaya" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCvM8eKGNLV5CzGEu2zNUbl74zHQq4O8UDeV1WoTGLh5Xdpd-NkVnPG250nSMJdl4VeCEH2dTrXDkxGyVK7XyHEP-BZPziSQzDPpJS0YgaoRC4-hZ6euzdQLa7SHnmQFCQj7f_cnniv7-OspoYEVWEsmTRlmwCIm7JaPZMwtLqo1Hko5CL4nAlu32c5O8wKRCggaz9FIdju2jBRs6Klwb8Vqn4JGPhr8HD1k35J7VDaR0Tztn0ddSINukUK5lPEVs9X9ZktahDSI7c"/>
                    </div>
                    <div>
                        <p class="font-bold text-sm text-primary">Dr. Andi Wijaya</p>
                        <p class="text-xs text-on-surface-variant">Dewan Pakar ISOC ID</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Technical Guide -->
        <div class="col-span-12 md:col-span-6 lg:col-span-4">
            <div class="h-full rounded-xl p-8 bg-surface-container-low border border-outline-variant/10 hover:bg-surface-container-high transition-colors">
                <span class="material-symbols-outlined text-secondary mb-6 block text-3xl">code_blocks</span>
                <h3 class="text-xl font-headline font-bold text-primary mb-3">Policy Brief: Perlindungan Data Pribadi di Indonesia</h3>
                <p class="text-sm text-on-surface-variant leading-relaxed mb-6">Rekomendasi kebijakan untuk penguatan regulasi perlindungan data dan hak digital warga negara.</p>
                <a class="text-secondary font-bold text-sm flex items-center gap-2 group/link" href="#">
                    Lihat Panduan
                    <span class="material-symbols-outlined text-sm group-hover/link:translate-x-1 transition-transform">chevron_right</span>
                </a>
            </div>
        </div>

        <!-- Annual Report -->
        <div class="col-span-12 md:col-span-6 lg:col-span-4">
            <div class="h-full rounded-xl p-8 bg-surface-container-lowest border border-outline-variant/10 flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 bg-tertiary-fixed rounded-lg flex items-center justify-center mb-6">
                        <span class="material-symbols-outlined text-on-tertiary-fixed">description</span>
                    </div>
                    <h3 class="text-xl font-headline font-bold text-primary mb-2">ISOC 2030 Strategy</h3>
                    <p class="text-sm text-on-surface-variant">Strategi jangka panjang ISOC untuk menjawab kesenjangan digital dan menurunnya kepercayaan terhadap internet.</p>
                </div>
                <div class="mt-8 pt-4 border-t border-outline-variant/20 flex justify-between items-center">
                    <span class="text-xs font-bold text-on-surface-variant/60">PDF &bull; 12.4 MB</span>
                    <button class="text-primary hover:text-secondary transition-colors">
                        <span class="material-symbols-outlined">download</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Community Module -->
        <div class="col-span-12 md:col-span-6 lg:col-span-4">
            <div class="h-full rounded-xl p-8 bg-primary text-white flex flex-col justify-center relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <span class="material-symbols-outlined text-[120px]">groups</span>
                </div>
                <h3 class="text-2xl font-headline font-bold mb-4 relative z-10">Kontribusi Riset</h3>
                <p class="text-white/70 mb-8 relative z-10">Punya riset atau kajian tentang internet, perlindungan data, atau tata kelola digital? Bagikan karya Anda di pusat pengetahuan kami.</p>
                <button class="w-full bg-white text-primary py-3 rounded-lg font-bold hover:bg-secondary-fixed transition-all relative z-10">Kirim Naskah</button>
            </div>
        </div>
    </div>

    <!-- Secondary Resources List -->
    <section class="mt-24">
        <h2 class="text-3xl font-headline font-bold text-primary mb-10 pb-4 border-b-2 border-primary-container inline-block">Publikasi & Materi Lainnya</h2>
        <div class="space-y-0">
            <div class="group flex flex-col md:flex-row md:items-center py-8 border-b border-outline-variant/30 hover:bg-surface-container-lowest transition-all px-4 -mx-4 rounded-xl">
                <div class="md:w-32 text-on-surface-variant font-mono text-sm mb-2 md:mb-0">01 / BRIEF</div>
                <div class="flex-1">
                    <h4 class="text-xl font-headline font-semibold group-hover:text-secondary transition-colors">Internet Impact Brief: Konektivitas di Daerah 3T Indonesia</h4>
                    <p class="text-on-surface-variant mt-1">Analisis dampak internet terhadap pendidikan dan ekonomi di daerah terdepan, terluar, dan tertinggal.</p>
                </div>
                <div class="md:w-48 text-right mt-4 md:mt-0">
                    <span class="text-xs text-on-surface-variant bg-surface-container-high px-3 py-1 rounded-full">PDF Download</span>
                </div>
            </div>
            <div class="group flex flex-col md:flex-row md:items-center py-8 border-b border-outline-variant/30 hover:bg-surface-container-lowest transition-all px-4 -mx-4 rounded-xl">
                <div class="md:w-32 text-on-surface-variant font-mono text-sm mb-2 md:mb-0">02 / REPORT</div>
                <div class="flex-1">
                    <h4 class="text-xl font-headline font-semibold group-hover:text-secondary transition-colors">Impact Report: Program Literasi Digital 2024</h4>
                    <p class="text-on-surface-variant mt-1">Laporan dampak program pelatihan literasi digital bagi siswa SMA/SMK dan guru.</p>
                </div>
                <div class="md:w-48 text-right mt-4 md:mt-0">
                    <span class="text-xs text-on-surface-variant bg-surface-container-high px-3 py-1 rounded-full">PDF Download</span>
                </div>
            </div>
            <div class="group flex flex-col md:flex-row md:items-center py-8 border-b border-outline-variant/30 hover:bg-surface-container-lowest transition-all px-4 -mx-4 rounded-xl">
                <div class="md:w-32 text-on-surface-variant font-mono text-sm mb-2 md:mb-0">03 / POLICY</div>
                <div class="flex-1">
                    <h4 class="text-xl font-headline font-semibold group-hover:text-secondary transition-colors">Policy Brief: Tata Kelola Internet dan Hak Digital</h4>
                    <p class="text-on-surface-variant mt-1">Rekomendasi kebijakan untuk ekosistem internet yang terbuka dan terpercaya di Indonesia.</p>
                </div>
                <div class="md:w-48 text-right mt-4 md:mt-0">
                    <span class="text-xs text-on-surface-variant bg-surface-container-high px-3 py-1 rounded-full">5 Menit Baca</span>
                </div>
            </div>
        </div>
        <div class="mt-12 text-center">
            <button class="bg-surface-container-highest text-primary px-8 py-3 rounded-full font-bold hover:bg-surface-dim transition-all">Muat Lebih Banyak</button>
        </div>
    </section>
</div>
@endsection
