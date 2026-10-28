@php
    $url = fn ($k) => ($v = $section->setting($k)) ? \Illuminate\Support\Facades\Storage::disk('public')->url($v) : null;
    $ios = $section->setting('ios_url') ?: 'https://apps.apple.com/us/app/on-air-media-group/id6762763280';
    $android = $section->setting('android_url') ?: 'https://play.google.com/store/apps/details?id=gr.radio.onairmediagroup';
    $features = array_values(array_filter((array) $section->setting('features'), 'is_string')) ?: [
        'Όλοι οι 12 σταθμοί μας ζωντανά, με ένα άγγιγμα',
        'Στο αυτοκίνητο με Apple CarPlay και Android Auto',
        'Τι παίζει τώρα σε κάθε σταθμό',
        'Δωρεάν, χωρίς εγγραφή',
    ];
    $phone = $url('phone_image');
    $right = $section->setting('layout', 'image-right') !== 'image-left';
    $qr = $section->setting('qr', true);
    $stations = $phone ? collect() : \App\Models\Station::query()->active()->whereNotNull('logo_path')->orderBy('sort')->limit(9)->get();
@endphp
<section class="mx-auto max-w-7xl px-4 sm:px-6 py-20" aria-labelledby="app-{{ $section->id }}">
    <div class="relative overflow-hidden rounded-[2rem] border border-line bg-surface">
        <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-onair/15 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-24 right-10 h-72 w-72 rounded-full bg-glow/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative grid items-center gap-10 p-8 sm:p-12 lg:grid-cols-[1.15fr_1fr]">
            <div class="{{ $right ? '' : 'lg:order-2' }}">
                <p class="inline-flex items-center gap-2 rounded-full bg-onair/10 px-3 py-1 text-sm font-bold text-onair"><x-ui-icon name="phone" class="h-4 w-4" /> Δωρεάν εφαρμογή</p>
                <h2 id="app-{{ $section->id }}" class="mt-4 text-3xl sm:text-4xl font-extrabold tracking-tight">{{ $section->title ?: 'Όλοι οι σταθμοί μας, στην τσέπη σας' }}</h2>
                <p class="mt-3 text-lg text-slate">{{ $section->subtitle ?: 'Κατεβάστε την εφαρμογή On Air Media Group και ακούστε όπου κι αν είστε.' }}</p>
                <ul class="mt-6 space-y-3">
                    @foreach ($features as $f)
                        <li class="flex items-start gap-3"><span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-onair text-white"><x-ui-icon name="check" class="h-3.5 w-3.5" /></span><span>{{ $f }}</span></li>
                    @endforeach
                </ul>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    @foreach ([['ios', $ios, 'Λήψη για iPhone', 'App Store'], ['android', $android, 'Λήψη για Android', 'Google Play']] as [$k, $link, $label, $store])
                        <a href="{{ $link }}" target="_blank" rel="noopener" class="inline-flex" aria-label="{{ $label }} από το {{ $store }}">
                            @if ($badge = $url($k.'_badge'))
                                <img src="{{ $badge }}" alt="{{ $label }} από το {{ $store }}" class="h-12 w-auto" loading="lazy">
                            @else
                                <span class="inline-flex items-center gap-3 rounded-xl bg-night px-5 py-2.5 text-white ring-1 ring-white/10 hover:bg-night-2">
                                    <x-ui-icon name="download" class="h-6 w-6" />
                                    <span class="leading-tight"><span class="block text-[11px] text-white/70">{{ $label }}</span><span class="block font-bold">{{ $store }}</span></span>
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>

                @if ($qr)
                    <div class="mt-8 hidden items-center gap-6 lg:flex">
                        @foreach ([['iPhone', $ios], ['Android', $android]] as [$label, $link])
                            @if ($svg = \App\Support\Qr::svg($link))
                                <div class="flex items-center gap-3">
                                    <span class="block h-20 w-20 rounded-xl bg-white p-1.5 shadow-sm ring-1 ring-black/5">{!! $svg !!}</span>
                                    <span class="text-sm text-slate">Σκανάρετε<br>για {{ $label }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex justify-center {{ $right ? '' : 'lg:order-1' }}">
                <div class="relative w-60 sm:w-64 rotate-2 rounded-[2.6rem] bg-night p-3 shadow-2xl ring-1 ring-white/10">
                    <div class="absolute left-1/2 top-3 z-10 h-5 w-24 -translate-x-1/2 rounded-full bg-black" aria-hidden="true"></div>
                    <div class="aspect-[9/19.5] overflow-hidden rounded-[2rem]" style="background: linear-gradient(160deg, #c80c92, #121a33 70%)">
                        @if ($phone)
                            <img src="{{ $phone }}" alt="Η εφαρμογή On Air Media Group" class="h-full w-full object-cover" loading="lazy">
                        @else
                            <div class="flex h-full flex-col px-5 pb-6 pt-12 text-white">
                                <p class="text-xs font-bold uppercase tracking-widest text-white/70">Στον αέρα τώρα</p>
                                <p class="mt-1 text-xl font-extrabold">On Air Media Group</p>
                                <div class="mt-5 grid grid-cols-3 gap-2.5">
                                    @foreach ($stations as $st)
                                        <span class="grid aspect-square place-items-center overflow-hidden rounded-xl bg-white p-1"><img src="{{ $st->logo_url }}" alt="{{ $st->name }}" class="h-full w-full object-contain" loading="lazy"></span>
                                    @endforeach
                                </div>
                                <div class="mt-auto flex items-center gap-3 rounded-2xl bg-white/10 p-3 backdrop-blur">
                                    <span class="grid h-10 w-10 place-items-center rounded-full bg-onair"><x-ui-icon name="play" class="h-4 w-4" /></span>
                                    <span class="text-sm leading-tight"><span class="block font-bold">12 σταθμοί</span><span class="text-white/70">ζωντανά</span></span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
