<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    @php
        $templates = $this->templates();
        $activeTemplateId = $this->activeTemplateId();
    @endphp

    <style>
        .materi-scope-layout {
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(230px, 300px) minmax(0, 1fr);
        }

        .materi-scope-list,
        .materi-scope-table {
            min-width: 0;
        }

        .materi-scope-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .04);
            overflow: hidden;
        }

        .materi-scope-head {
            border-bottom: 1px solid #eef2f7;
            padding: 16px;
        }

        .materi-scope-title {
            color: #111827;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.35;
            margin: 0;
        }

        .materi-scope-subtitle {
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
            margin: 4px 0 0;
        }

        .materi-scope-items {
            display: grid;
            gap: 8px;
            max-height: calc(100vh - 260px);
            overflow: auto;
            padding: 10px;
        }

        .materi-scope-link {
            border: 1px solid transparent;
            border-radius: 11px;
            color: #334155;
            display: block;
            padding: 12px;
            text-decoration: none;
            transition: background .2s ease, border-color .2s ease, color .2s ease;
        }

        .materi-scope-link:hover,
        .materi-scope-link.is-active {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #c2410c;
        }

        .materi-scope-name {
            display: block;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.4;
        }

        .materi-scope-meta {
            color: #64748b;
            display: block;
            font-size: 12px;
            margin-top: 5px;
        }

        .materi-scope-empty {
            color: #64748b;
            font-size: 13px;
            padding: 18px;
            text-align: center;
        }

        @media (max-width: 1024px) {
            .materi-scope-layout {
                grid-template-columns: 1fr;
            }

            .materi-scope-items {
                max-height: none;
            }
        }
    </style>

    <div class="flex flex-col gap-y-6">
        <x-filament-panels::resources.tabs />

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE, scopes: $this->getRenderHookScopes()) }}

        <div class="materi-scope-layout">
            <aside class="materi-scope-list">
                <div class="materi-scope-card">
                    <div class="materi-scope-head">
                        <h2 class="materi-scope-title">Materi Event</h2>
                        <p class="materi-scope-subtitle">Pilih materi untuk menampilkan data di tabel kanan.</p>
                    </div>

                    <div class="materi-scope-items">
                        @forelse ($templates as $template)
                            <a
                                href="{{ $this->templateUrl($template->id) }}"
                                class="materi-scope-link {{ $activeTemplateId === $template->id ? 'is-active' : '' }}"
                            >
                                <span class="materi-scope-name">{{ $template->name }}</span>
                                <span class="materi-scope-meta">{{ $this->templateCounter($template) }} {{ $this->templateCounterLabel() }}</span>
                            </a>
                        @empty
                            <div class="materi-scope-empty">Belum ada Materi Event.</div>
                        @endforelse
                    </div>
                </div>
            </aside>

            <section class="materi-scope-table">
                {{ $this->table }}
            </section>
        </div>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER, scopes: $this->getRenderHookScopes()) }}
    </div>
</x-filament-panels::page>
