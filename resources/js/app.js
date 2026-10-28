/*
 * On Air Media — player που δεν σταματά.
 * Το <audio> ζει μέσα σε @persist('player') και η πλοήγηση γίνεται με wire:navigate,
 * άρα ο ήχος συνεχίζει από σελίδα σε σελίδα. Το Alpine έρχεται από το Livewire.
 */

const STORAGE_KEY = 'onair:last-station';
const VOLUME_KEY = 'onair:volume';
const POLL_MS = 20000;

const storage = {
    get(key) { try { return localStorage.getItem(key); } catch { return null; } },
    set(key, value) { try { localStorage.setItem(key, value); } catch { /* private mode */ } },
};

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    /* Θέμα: ακολουθεί τη συσκευή μέχρι ο χρήστης να διαλέξει */
    Alpine.store('theme', {
        dark: document.documentElement.classList.contains('dark'),
        init() {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!storage.get('onair:theme')) this.apply(e.matches);
            });
        },
        apply(dark) {
            this.dark = dark;
            document.documentElement.classList.toggle('dark', dark);
        },
        toggle() {
            this.apply(!this.dark);
            storage.set('onair:theme', this.dark ? 'dark' : 'light');
        },
    });

    Alpine.store('radio', {
        stations: [],
        current: null,
        playing: false,
        loading: false,
        error: null,
        volume: 0.8,
        np: {},
        _audio: null,
        _timer: null,

        init() {
            const data = document.getElementById('stations-data');
            this.stations = data ? JSON.parse(data.textContent) : [];
            const v = parseFloat(storage.get(VOLUME_KEY));
            if (!Number.isNaN(v)) this.volume = Math.min(1, Math.max(0, v));
            const last = storage.get(STORAGE_KEY);
            this.current = this.find(last) ?? this.stations[0] ?? null;
            this.poll();
            document.addEventListener('visibilitychange', () => { if (!document.hidden) this.poll(); });
        },

        audio() {
            if (!this._audio) {
                this._audio = document.getElementById('radio-audio');
                const a = this._audio;
                a.volume = this.volume;
                a.addEventListener('playing', () => { this.playing = true; this.loading = false; this.error = null; });
                a.addEventListener('waiting', () => { this.loading = true; });
                a.addEventListener('pause', () => { this.playing = false; this.loading = false; });
                a.addEventListener('error', () => {
                    if (!a.getAttribute('src')) return;
                    this.playing = false; this.loading = false;
                    this.error = 'Ο σταθμός δεν απαντά αυτή τη στιγμή. Δοκιμάστε ξανά σε λίγο.';
                });
            }
            return this._audio;
        },

        find(slug) { return this.stations.find((s) => s.slug === slug) ?? null; },

        isPlaying(slug) { return this.playing && this.current?.slug === slug; },

        async play(slug) {
            const station = slug ? this.find(slug) : this.current;
            if (!station) return;
            const a = this.audio();
            if (this.current?.slug === station.slug && this.playing) return;
            this.current = station;
            storage.set(STORAGE_KEY, station.slug);
            this.loading = true;
            this.error = null;
            // Ζωντανή ροή: πάντα «φρέσκο» src για να μην παίζει buffer από πριν
            a.src = station.stream;
            try { await a.play(); } catch { this.loading = false; }
            this.updateMediaSession();
        },

        stop() {
            const a = this.audio();
            a.pause();
            a.removeAttribute('src');
            a.load();
            this.playing = false;
        },

        toggle(slug) {
            if (slug && slug !== this.current?.slug) return this.play(slug);
            return this.playing ? this.stop() : this.play();
        },

        step(dir) {
            const i = this.stations.findIndex((s) => s.slug === this.current?.slug);
            const next = this.stations[(i + dir + this.stations.length) % this.stations.length];
            this.play(next.slug);
        },

        setVolume(v) {
            this.volume = Math.min(1, Math.max(0, Number(v)));
            this.audio().volume = this.volume;
            storage.set(VOLUME_KEY, String(this.volume));
        },

        nowLine(slug) {
            const n = this.np[slug];
            if (!n || (!n.title && !n.artist)) return '';
            return [n.artist, n.title].filter(Boolean).join(' – ');
        },

        async poll() {
            clearTimeout(this._timer);
            if (!document.hidden) {
                try {
                    const r = await fetch('/api/now-playing', { headers: { Accept: 'application/json' } });
                    if (r.ok) { this.np = await r.json(); this.updateMediaSession(); }
                } catch { /* σιωπηλά: ο player δουλεύει και χωρίς «τι παίζει» */ }
            }
            this._timer = setTimeout(() => this.poll(), POLL_MS);
        },

        updateMediaSession() {
            if (!('mediaSession' in navigator) || !this.current) return;
            const n = this.np[this.current.slug] ?? {};
            navigator.mediaSession.metadata = new MediaMetadata({
                title: n.title || this.current.name,
                artist: n.artist || this.current.name,
                album: `${this.current.name} · On Air Media`,
                artwork: this.current.logo ? [{ src: this.current.logo, sizes: '512x512' }] : [],
            });
            navigator.mediaSession.setActionHandler('play', () => this.play());
            navigator.mediaSession.setActionHandler('pause', () => this.stop());
            navigator.mediaSession.setActionHandler('stop', () => this.stop());
            navigator.mediaSession.setActionHandler('previoustrack', () => this.step(-1));
            navigator.mediaSession.setActionHandler('nexttrack', () => this.step(1));
        },
    });

    /* Slider αρχικής */
    Alpine.data('heroSlider', (count, ms = 7000) => ({
        i: 0,
        count,
        ms,
        paused: false,
        timer: null,
        reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        init() {
            this.$el.style.setProperty('--slide-ms', `${ms}ms`);
            if (count > 1 && !this.reduced) this.schedule();
        },
        destroy() { clearTimeout(this.timer); },
        schedule() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => { if (!this.paused) this.go(this.i + 1); else this.schedule(); }, this.ms);
        },
        go(n) {
            this.i = (n + this.count) % this.count;
            if (!this.reduced) this.schedule();
        },
        pause(v) {
            this.paused = v;
            if (!v && !this.reduced && this.count > 1) this.schedule();
        },
    }));
});
