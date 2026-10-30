<div x-data class="fixed inset-x-0 bottom-0 z-50 bg-night text-white border-t border-white/10 shadow-[0_-8px_30px_rgb(0_0_0/.25)]"
     role="region" aria-label="Ραδιοφωνικός player">
    <audio id="radio-audio" preload="none"></audio>

    <template x-if="$store.radio.current">
        <div class="mx-auto max-w-7xl px-3 sm:px-6 h-20 flex items-center gap-3 sm:gap-4">
            {{-- Σταθμός --}}
            <a :href="$store.radio.current.url" wire:navigate class="flex items-center gap-3 min-w-0 flex-1 group">
                <span class="h-12 w-12 shrink-0 rounded-lg bg-white grid place-items-center overflow-hidden ring-2"
                      :style="`--tw-ring-color:${$store.radio.current.color}`">
                    <img :src="$store.radio.current.logo" :alt="''" class="max-h-10 max-w-10 object-contain">
                </span>
                <span class="min-w-0">
                    <span class="flex items-center gap-2 text-sm font-semibold">
                        <span class="truncate group-hover:underline" x-text="$store.radio.current.name"></span>
                        <span x-show="$store.radio.playing" class="eq text-glow" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
                    </span>
                    <span class="block truncate text-[13px] text-white/70"
                          x-text="$store.radio.error || $store.radio.nowLine($store.radio.current.slug) || $store.radio.current.freq"
                          aria-live="polite"></span>
                </span>
            </a>

            {{-- Χειριστήρια --}}
            <div class="flex items-center gap-1 sm:gap-2">
                <button type="button" @click="$store.radio.step(-1)" class="hidden sm:grid h-10 w-10 place-items-center rounded-full hover:bg-white/10" aria-label="Προηγούμενος σταθμός">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
                </button>
                <button type="button" @click="$store.radio.toggle()"
                        class="h-12 w-12 grid place-items-center rounded-full bg-onair hover:bg-onair-dark"
                        :aria-label="$store.radio.playing ? 'Διακοπή' : 'Ακρόαση ' + $store.radio.current.name">
                    <svg x-show="$store.radio.loading" class="h-6 w-6 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3"/></svg>
                    <svg x-show="!$store.radio.loading && !$store.radio.playing" class="h-6 w-6 ml-0.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                    <svg x-show="!$store.radio.loading && $store.radio.playing" x-cloak class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 6h12v12H6z"/></svg>
                </button>
                <button type="button" @click="$store.radio.step(1)" class="hidden sm:grid h-10 w-10 place-items-center rounded-full hover:bg-white/10" aria-label="Επόμενος σταθμός">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 6h2v12h-2zM6 18l8.5-6L6 6z"/></svg>
                </button>
            </div>

            {{-- Ένταση --}}
            <label class="hidden lg:flex items-center gap-2 w-40">
                <span class="sr-only">Ένταση</span>
                <svg class="h-5 w-5 text-white/70 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 9v6h4l5 5V4L8 9H4zm12.5 3a4.5 4.5 0 00-2.5-4v8a4.5 4.5 0 002.5-4z"/></svg>
                <input type="range" min="0" max="1" step="0.05" :value="$store.radio.volume"
                       @input="$store.radio.setVolume($event.target.value)" class="w-full accent-glow">
            </label>
        </div>
    </template>
</div>
