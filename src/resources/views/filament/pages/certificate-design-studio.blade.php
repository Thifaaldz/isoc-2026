<x-filament-panels::page>
    @php
        $template = $this->template();
        $width = $template?->width_mm ?? 297;
        $height = $template?->height_mm ?? 210;
        $background = $this->imageUrl($template?->background_image);
    @endphp

    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="grid gap-4 lg:grid-cols-[1fr_1fr_auto_auto] lg:items-end">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Desain Sertifikat</span>
                    <select wire:model.live="templateId" class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950">
                        @foreach ($this->templateOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Tambah dari</span>
                    <select wire:model="newElementType" class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950">
                        @foreach ($this->elementTypeOptions() as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <x-filament::button wire:click="addElement" icon="heroicon-o-plus">
                    Tambah
                </x-filament::button>

                <x-filament::button wire:click="saveDesign" icon="heroicon-o-check">
                    Simpan Desain
                </x-filament::button>
            </div>
        </div>

        @if (! $template)
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900">
                Belum ada desain sertifikat. Buat dulu dari menu Desain Sertifikat.
            </div>
        @else
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_560px] 2xl:grid-cols-[minmax(0,1fr)_680px]">
                <div class="overflow-auto rounded-xl border border-gray-200 bg-gray-100 p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950 xl:sticky xl:top-6 xl:self-start">
                    <div
                        id="cert-canvas"
                        data-cert-canvas
                        data-width-mm="{{ $width }}"
                        data-height-mm="{{ $height }}"
                        class="relative mx-auto overflow-hidden bg-white shadow"
                        style="width: min(100%, {{ $width * 3 }}px); aspect-ratio: {{ $width }} / {{ $height }}; background-color: {{ $template->background_color ?? '#ffffff' }};"
                    >
                        @if ($background)
                            <img src="{{ $background }}" alt="" class="absolute inset-0 h-full w-full">
                        @else
                            <div class="absolute left-3 top-3 rounded-md bg-white/80 px-2 py-1 text-xs text-gray-500 ring-1 ring-gray-200 dark:bg-gray-900/80 dark:text-gray-300 dark:ring-gray-700">
                                Background belum tersimpan
                            </div>
                        @endif

                        @foreach ($elements as $index => $element)
                            @php
                                $type = $element['type'] ?? 'custom_text';
                                $elementWidth = (float) ($element['width'] ?? 40);
                                $elementHeight = (float) ($element['height'] ?? 10);
                                $fontSize = (float) ($element['font_size'] ?? 12);
                                $elementX = (float) ($element['x'] ?? 0);
                                $elementY = (float) ($element['y'] ?? 0);
                                $elementXPercent = ($elementX / $width) * 100;
                                $elementYPercent = ($elementY / $height) * 100;
                                $elementWidthPercent = ($elementWidth / $width) * 100;
                                $elementHeightPercent = ($elementHeight / $height) * 100;
                                $previewFontSize = max(8, $fontSize * 1.2);
                                $image = $this->imageUrl($element['image_path'] ?? null);
                            @endphp

                            <div
                                wire:key="certificate-canvas-element-{{ $index }}"
                                data-cert-element
                                data-index="{{ $index }}"
                                data-x="{{ $elementX }}"
                                data-y="{{ $elementY }}"
                                data-width-mm="{{ $elementWidth }}"
                                data-height-mm="{{ $elementHeight }}"
                                style="left: {{ $elementXPercent }}%; top: {{ $elementYPercent }}%; width: {{ $elementWidthPercent }}%; height: {{ $elementHeightPercent }}%; font-size: {{ $previewFontSize }}px; touch-action: none;"
                                class="group absolute cursor-move select-none overflow-hidden border border-primary-500/50 bg-white/65 p-1 text-center leading-tight ring-1 ring-white/60 transition hover:bg-primary-50 dark:bg-gray-900/70 dark:hover:bg-primary-950"
                                title="Geser elemen. Tarik kotak kecil kanan bawah untuk ubah ukuran."
                            >
                                @if (in_array($type, ['logo', 'partner_logo', 'signature_image'], true))
                                    @if ($image)
                                        <img src="{{ $image }}" alt="" class="h-full w-full object-contain">
                                    @else
                                        <span class="text-xs text-gray-500">Gambar</span>
                                    @endif
                                @elseif ($type === 'qr_code')
                                    <span class="grid h-full place-items-center text-xs font-semibold text-gray-500">QR</span>
                                @else
                                    <span style="font-weight: {{ $element['font_weight'] ?? '400' }}; color: {{ $element['color'] ?? '#111827' }}; text-align: {{ $element['align'] ?? 'center' }};">
                                        {!! nl2br(e($this->previewValue($element))) !!}
                                    </span>
                                @endif
                                <span
                                    data-resize-handle
                                    class="absolute bottom-0 right-0 h-3 w-3 cursor-se-resize border-l border-t border-primary-600 bg-primary-500 opacity-80 group-hover:opacity-100"
                                    title="Ubah ukuran"
                                ></span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                        <div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Layer Editor</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ count($elements) }} elemen</div>
                        </div>

                        <x-filament::button wire:click="saveDesign" icon="heroicon-o-check" size="sm">
                            Simpan
                        </x-filament::button>
                    </div>

                    <div class="max-h-[calc(100vh-220px)] overflow-y-auto p-3">
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                            @foreach ($elements as $index => $element)
                                <div wire:key="certificate-side-element-{{ $index }}" class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-950">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $element['label'] ?? 'Elemen ' . ($index + 1) }}
                                            </div>
                                            <div class="truncate text-xs text-gray-500 dark:text-gray-400">
                                                {{ $this->elementTypeOptions()[$element['type'] ?? 'custom_text'] ?? 'Elemen' }}
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            wire:click="removeElement({{ $index }})"
                                            class="shrink-0 rounded-md p-1.5 text-danger-600 transition hover:bg-danger-50 dark:hover:bg-danger-950"
                                            title="Hapus elemen"
                                        >
                                            <x-filament::icon icon="heroicon-o-trash" class="h-5 w-5" />
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2.5">
                                        <label class="col-span-2 block">
                                            <span class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Layer</span>
                                            <input wire:model.blur="elements.{{ $index }}.label" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                                        </label>

                                        @if (in_array($element['type'] ?? 'custom_text', ['custom_text', 'signature'], true))
                                            <label class="col-span-2 block">
                                                <span class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Tulisan</span>
                                                <textarea wire:model.blur="elements.{{ $index }}.content" rows="2" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"></textarea>
                                            </label>
                                        @endif

                                        <label class="block">
                                            <span class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">X</span>
                                            <input type="number" step="0.1" wire:model.blur="elements.{{ $index }}.x" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                                        </label>
                                        <label class="block">
                                            <span class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Y</span>
                                            <input type="number" step="0.1" wire:model.blur="elements.{{ $index }}.y" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                                        </label>
                                        <label class="block">
                                            <span class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Lebar</span>
                                            <input type="number" step="0.1" wire:model.blur="elements.{{ $index }}.width" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                                        </label>
                                        <label class="block">
                                            <span class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Tinggi</span>
                                            <input type="number" step="0.1" wire:model.blur="elements.{{ $index }}.height" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                                        </label>

                                        @if (! in_array($element['type'] ?? 'custom_text', ['logo', 'partner_logo', 'signature_image', 'qr_code'], true))
                                            <label class="block">
                                                <span class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Font</span>
                                                <input type="number" step="1" wire:model.blur="elements.{{ $index }}.font_size" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                                            </label>
                                            <label class="block">
                                                <span class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Tebal</span>
                                                <select wire:model.blur="elements.{{ $index }}.font_weight" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                                                    <option value="400">Normal</option>
                                                    <option value="600">Semi Bold</option>
                                                    <option value="700">Bold</option>
                                                </select>
                                            </label>
                                            <label class="block">
                                                <span class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Rata</span>
                                                <select wire:model.blur="elements.{{ $index }}.align" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                                                    <option value="left">Kiri</option>
                                                    <option value="center">Tengah</option>
                                                    <option value="right">Kanan</option>
                                                </select>
                                            </label>
                                            <label class="block">
                                                <span class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Warna</span>
                                                <input type="color" wire:model.blur="elements.{{ $index }}.color" class="h-9 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                                            </label>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @script
        <script>
            (() => {
                if (window.__certificateStudioDragReady) {
                    return;
                }

                window.__certificateStudioDragReady = true;

                let active = null;

                const number = (value, fallback = 0) => {
                    const parsed = Number.parseFloat(value);

                    return Number.isFinite(parsed) ? parsed : fallback;
                };

                const moveElement = (element, canvas, x, y, width = null, height = null) => {
                    const canvasWidth = number(canvas.dataset.widthMm, 297);
                    const canvasHeight = number(canvas.dataset.heightMm, 210);

                    element.dataset.x = String(x);
                    element.dataset.y = String(y);
                    element.style.left = `${(x / canvasWidth) * 100}%`;
                    element.style.top = `${(y / canvasHeight) * 100}%`;

                    if (width !== null) {
                        element.dataset.widthMm = String(width);
                        element.style.width = `${(width / canvasWidth) * 100}%`;
                    }

                    if (height !== null) {
                        element.dataset.heightMm = String(height);
                        element.style.height = `${(height / canvasHeight) * 100}%`;
                    }
                };

                document.addEventListener('pointerdown', (event) => {
                    const element = event.target.closest('[data-cert-element]');
                    const handle = event.target.closest('[data-resize-handle]');

                    if (! element) {
                        return;
                    }

                    const canvas = element.closest('[data-cert-canvas]');

                    if (! canvas) {
                        return;
                    }

                    event.preventDefault();

                    active = {
                        canvas,
                        element,
                        index: Number.parseInt(element.dataset.index, 10),
                        startClientX: event.clientX,
                        startClientY: event.clientY,
                        originX: number(element.dataset.x),
                        originY: number(element.dataset.y),
                        originWidth: number(element.dataset.widthMm, 40),
                        originHeight: number(element.dataset.heightMm, 10),
                        width: number(element.dataset.widthMm, 40),
                        height: number(element.dataset.heightMm, 10),
                        canvasWidth: number(canvas.dataset.widthMm, 297),
                        canvasHeight: number(canvas.dataset.heightMm, 210),
                        mode: handle ? 'resize' : 'move',
                    };

                    element.setPointerCapture?.(event.pointerId);
                    element.classList.add('ring-2', 'ring-primary-500');
                });

                document.addEventListener('pointermove', (event) => {
                    if (! active) {
                        return;
                    }

                    const rect = active.canvas.getBoundingClientRect();
                    const mmXPerPixel = active.canvasWidth / rect.width;
                    const mmYPerPixel = active.canvasHeight / rect.height;
                    const deltaX = (event.clientX - active.startClientX) * mmXPerPixel;
                    const deltaY = (event.clientY - active.startClientY) * mmYPerPixel;

                    if (active.mode === 'resize') {
                        const nextWidth = Math.max(5, Math.min(active.canvasWidth - active.originX, Math.round((active.originWidth + deltaX) * 10) / 10));
                        const nextHeight = Math.max(5, Math.min(active.canvasHeight - active.originY, Math.round((active.originHeight + deltaY) * 10) / 10));

                        active.width = nextWidth;
                        active.height = nextHeight;
                        moveElement(active.element, active.canvas, active.originX, active.originY, nextWidth, nextHeight);

                        return;
                    }

                    const nextX = active.originX + deltaX;
                    const nextY = active.originY + deltaY;
                    const clampedX = Math.max(0, Math.min(active.canvasWidth - active.width, Math.round(nextX * 10) / 10));
                    const clampedY = Math.max(0, Math.min(active.canvasHeight - active.height, Math.round(nextY * 10) / 10));

                    moveElement(active.element, active.canvas, clampedX, clampedY);
                });

                document.addEventListener('pointerup', () => {
                    if (! active) {
                        return;
                    }

                    const componentRoot = active.canvas.closest('[wire\\:id]');
                    const componentId = componentRoot?.getAttribute('wire:id');
                    const component = componentId ? window.Livewire?.find(componentId) : null;
                    const x = number(active.element.dataset.x);
                    const y = number(active.element.dataset.y);
                    const width = number(active.element.dataset.widthMm, active.width);
                    const height = number(active.element.dataset.heightMm, active.height);

                    active.element.classList.remove('ring-2', 'ring-primary-500');
                    component?.call('updateElementGeometry', active.index, x, y, width, height);
                    active = null;
                });
            })();
        </script>
    @endscript
</x-filament-panels::page>
