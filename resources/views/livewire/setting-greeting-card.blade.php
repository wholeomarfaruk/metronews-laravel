@php
    $inputClass = 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
    $labelClass = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400';
    $previewUrl = $template ? $template->temporaryUrl() : ($old_template ? asset($old_template) : null);
    $bgUrl = $background ? $background->temporaryUrl() : (!$remove_background && $old_background ? asset($old_background) : null);
@endphp
<div>
    {{-- Self-hosted fonts (php artisan greeting-card:fonts) so the preview matches the card --}}
    @foreach ($fonts as $font)
        <link rel="stylesheet" href="{{ asset('fonts/greeting-card/' . \Illuminate\Support\Str::slug($font) . '.css') }}">
    @endforeach

    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
        <!-- Breadcrumb Start -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Greeting Card</h2>
            <nav>
                <ol class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                    <li><a href="{{ route('admin.dashboard') }}">Home</a> /</li>
                    <li><a href="{{ route('admin.settings') }}">Settings</a> /</li>
                    <li class="text-gray-800 dark:text-white/90">Greeting Card</li>
                </ol>
            </nav>
        </div>
        <!-- Breadcrumb End -->

        <form wire:submit.prevent="updateSetting"
            class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] xl:p-10">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-800 dark:text-white/90">
                    <input type="checkbox" wire:model="enabled" class="h-4 w-4 rounded border-gray-300">
                    Public page enabled
                </label>
                <div class="flex items-center gap-3">
                    <a href="{{ route('greeting.card') }}" target="_blank"
                        class="text-sm text-blue-600 hover:underline">View public page ↗</a>
                    <button type="submit" wire:loading.attr="disabled"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Save</button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                {{-- Settings --}}
                <div class="space-y-4">
                    <div>
                        <label class="{{ $labelClass }}">Template PNG <span class="text-red-400">*</span></label>
                        <input wire:model="template" type="file" accept="image/png"
                            class="h-11 w-full overflow-hidden rounded-lg border border-gray-300 bg-transparent text-sm text-gray-500 file:mr-5 file:cursor-pointer file:border-0 file:border-r file:border-gray-200 file:bg-gray-50 file:py-3 file:px-3.5 file:text-sm file:text-gray-700 hover:file:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                        <p class="mt-1 text-xs text-gray-500">
                            PNG with a <b>transparent area</b> where the user's photo should show (e.g. 1080×1350).
                            The download is exported at the template's size.
                            @if ($template_width)
                                Current: {{ $template_width }}×{{ $template_height }}px
                            @endif
                        </p>
                        <div wire:loading wire:target="template" class="text-xs text-gray-500">Uploading...</div>
                        @error('template') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Background image <span class="text-gray-400">(optional)</span></label>
                        <input wire:model="background" type="file" accept="image/png,image/jpeg,image/webp"
                            class="h-11 w-full overflow-hidden rounded-lg border border-gray-300 bg-transparent text-sm text-gray-500 file:mr-5 file:cursor-pointer file:border-0 file:border-r file:border-gray-200 file:bg-gray-50 file:py-3 file:px-3.5 file:text-sm file:text-gray-700 hover:file:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                        <p class="mt-1 text-xs text-gray-500">
                            Drawn <b>behind</b> the user's photo (e.g. white background with the faded logo).
                            Same size as the template is best. It shows only where the user's photo is transparent
                            (a cut-out PNG) or doesn't cover the card.
                        </p>
                        <div wire:loading wire:target="background" class="text-xs text-gray-500">Uploading...</div>
                        @error('background') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        @if ($bgUrl)
                            <div class="mt-2 flex items-center gap-3">
                                <img src="{{ $bgUrl }}" alt="Background" class="h-20 rounded border border-gray-200 dark:border-gray-700">
                                <button type="button" wire:click="removeBackground" class="text-sm text-red-600 hover:underline">Remove</button>
                            </div>
                        @elseif ($remove_background)
                            <p class="mt-1 text-xs text-orange-600">Background will be removed when you click Save.</p>
                        @endif
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Page title <span class="text-red-400">*</span></label>
                        <input type="text" wire:model="title" class="{{ $inputClass }}">
                        @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Page description</label>
                        <textarea wire:model="description" rows="3" class="{{ $inputClass }} h-auto"></textarea>
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <hr class="border-gray-200 dark:border-gray-800">
                    <h3 class="font-semibold text-gray-800 dark:text-white/90">Name style</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="{{ $labelClass }}">Font</label>
                            <select wire:model="font_family" class="{{ $inputClass }}">
                                @foreach ($fonts as $font)
                                    <option value="{{ $font }}">{{ $font }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Weight</label>
                            <select wire:model="font_weight" class="{{ $inputClass }}">
                                <option value="400">Regular</option>
                                <option value="700">Bold</option>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Default font size (px)</label>
                            <input type="number" wire:model="font_size" min="10" max="400" class="{{ $inputClass }}">
                            @error('font_size') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Color</label>
                            <input type="color" wire:model="name_color" class="h-11 w-full rounded-lg border border-gray-300 p-1">
                            @error('name_color') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Max name width (% of template width) — longer names shrink to fit</label>
                        <input type="number" wire:model="name_max_width" min="5" max="100" step="0.5" class="{{ $inputClass }}">
                        @error('name_max_width') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="{{ $labelClass }}">Name centre X (%)</label>
                            <input type="number" wire:model="name_x" min="0" max="100" step="0.01" class="{{ $inputClass }}">
                            @error('name_x') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Name centre Y (%)</label>
                            <input type="number" wire:model="name_y" min="0" max="100" step="0.01" class="{{ $inputClass }}">
                            @error('name_y') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Sample name (preview only)</label>
                        <input type="text" wire:model="sample_name" class="{{ $inputClass }}">
                    </div>
                </div>

                {{-- Preview --}}
                <div>
                    <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">
                        <b>Click on the template</b> where the name should be centred.
                    </p>
                    @if ($previewUrl)
                        <div class="relative mx-auto cursor-crosshair select-none overflow-hidden rounded-lg border border-gray-200 bg-[repeating-conic-gradient(#e5e7eb_0%_25%,#fff_0%_50%)] bg-[length:20px_20px] dark:border-gray-700"
                            style="max-width: 460px; container-type: inline-size;"
                            x-data="{
                                place(e) {
                                    const r = this.$el.getBoundingClientRect();
                                    this.$wire.name_x = +((e.clientX - r.left) / r.width * 100).toFixed(2);
                                    this.$wire.name_y = +((e.clientY - r.top) / r.height * 100).toFixed(2);
                                }
                            }"
                            @click="place($event)">
                            @if ($bgUrl)
                                <img src="{{ $bgUrl }}" alt="" class="absolute inset-0 h-full w-full object-cover" draggable="false">
                            @endif
                            <img src="{{ $previewUrl }}" alt="Template" class="relative block w-full" draggable="false">
                            <div class="pointer-events-none absolute border border-dashed border-yellow-400 text-center leading-none whitespace-nowrap"
                                :style="`left:${$wire.name_x}%; top:${$wire.name_y}%; width:${$wire.name_max_width}%;
                                    transform:translate(-50%,-50%); color:${$wire.name_color};
                                    font-family:'${$wire.font_family}', serif; font-weight:${$wire.font_weight};
                                    font-size:calc(${$wire.font_size} / ${$wire.template_width} * 100cqw);`"
                                x-text="$wire.sample_name"></div>
                            <div class="pointer-events-none absolute h-2 w-2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-red-500"
                                :style="`left:${$wire.name_x}%; top:${$wire.name_y}%`"></div>
                        </div>
                        <p class="mt-2 text-center text-xs text-gray-500">Background (if any) → user photo → template. Checkered area = transparent. Yellow box = max name width.</p>
                    @else
                        <div class="flex h-64 flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">
                            <span class="font-medium text-gray-700 dark:text-gray-300">No template uploaded</span>
                            <span>The public page uses the built-in Metro News design (1080×1350) until you upload a PNG.
                                Name position settings apply only to an uploaded template.</span>
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
