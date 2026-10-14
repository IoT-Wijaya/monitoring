{{-- MQTT Inspector — shadcn-inspired UI for Laravel Filament --}}
<x-filament-panels::page>
    @assets
    <script src="{{ asset('vendor/mqtt/mqtt.min.js') }}"></script>
    @endassets

    <style>
        [x-cloak] {
            display: none !important;
        }

        .mqtt-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #a1a1aa transparent;
        }

        .mqtt-scrollbar::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }

        .mqtt-scrollbar::-webkit-scrollbar-thumb {
            background: #a1a1aa;
            border-radius: 999px;
        }
    </style>

    <script>
        (function() {

            const register = () => {

                Alpine.data('mqttInspector', () => ({

                    // ---------- Koneksi ----------

                    brokerUrl: 'ws://broker.hivemq.com:8000/mqtt',

                    username: '',

                    password: '',

                    status: 'disconnected',

                    client: null,

                    subscribed: new Set(),

                    // ---------- Subscribe ----------

                    subs: ['pabrik/#'],

                    newSub: '',

                    activeFilter: null,

                    // ---------- Publish ----------

                    pubTopic: 'pabrik/sanding-besar/perintah',

                    pubPayload: '{"command": "START_CYCLE"}',

                    pubQos: 0,

                    pubRetain: false,

                    // ---------- Log pesan & Mobile Drawer ----------

                    messages: [],

                    nextId: 1,

                    maxMessages: 500,

                    autoScroll: true,

                    sidebarOpen: false, // Untuk toggle drawer di mobile

                    init() {

                        this.loadSettings();

                        ['brokerUrl', 'username', 'subs', 'pubTopic', 'pubPayload', 'pubQos', 'pubRetain']

                        .forEach((key) => this.$watch(key, () => this.saveSettings()));

                    },

                    destroy() {

                        this.disconnect(true);

                    },

                    loadSettings() {

                        try {

                            const s = JSON.parse(localStorage.getItem('nexora.mqtt.inspector') || '{}');

                            if (s.brokerUrl) this.brokerUrl = s.brokerUrl;

                            if (typeof s.username === 'string') this.username = s.username;

                            if (Array.isArray(s.subs)) this.subs = s.subs;

                            if (s.pubTopic) this.pubTopic = s.pubTopic;

                            if (typeof s.pubPayload === 'string') this.pubPayload = s.pubPayload;

                            if (s.pubQos !== undefined) this.pubQos = s.pubQos;

                            if (typeof s.pubRetain === 'boolean') this.pubRetain = s.pubRetain;

                        } catch (e) {}

                    },

                    saveSettings() {

                        try {

                            localStorage.setItem('nexora.mqtt.inspector', JSON.stringify({

                                brokerUrl: this.brokerUrl,

                                username: this.username,

                                subs: this.subs,

                                pubTopic: this.pubTopic,

                                pubPayload: this.pubPayload,

                                pubQos: this.pubQos,

                                pubRetain: this.pubRetain,

                            }));

                        } catch (e) {}

                    },

                    connect() {

                        if (this.status !== 'disconnected') return;

                        if (typeof mqtt === 'undefined') {

                            this.sys('Library mqtt.js belum termuat. Muat ulang halaman lalu coba lagi.', 'error');

                            return;

                        }

                        const url = this.brokerUrl.trim();

                        if (!/^wss?:\/\//i.test(url)) {

                            this.sys('Broker URL harus diawali ws:// atau wss://', 'error');

                            return;

                        }

                        if (location.protocol === 'https:' && /^ws:\/\//i.test(url)) {

                            this.sys('Halaman memakai HTTPS, browser memblokir ws://. Gunakan wss://', 'error');

                            return;

                        }

                        const options = {

                            clientId: 'nexora-inspector-' + Math.random().toString(16).slice(2, 10),

                            clean: true,

                            reconnectPeriod: 3000,

                            connectTimeout: 8000,

                        };

                        if (this.username) {

                            options.username = this.username;

                            options.password = this.password;

                        }

                        this.status = 'connecting';

                        this.sys('Menghubungkan ke ' + url + ' ...');

                        try {

                            this.client = mqtt.connect(url, options);

                        } catch (e) {

                            this.status = 'disconnected';

                            this.sys('Gagal membuat koneksi: ' + e.message, 'error');

                            return;

                        }

                        this.client.on('connect', () => {

                            this.status = 'connected';

                            this.sys('Terhubung ke broker.');

                            this.subs.forEach((t) => this.doSubscribe(t));

                        });

                        this.client.on('reconnect', () => {

                            this.status = 'connecting';

                        });

                        this.client.on('close', () => {

                            if (this.client && this.status === 'connected') {

                                this.status = 'connecting';

                                this.sys('Koneksi terputus, mencoba menyambung ulang...', 'error');

                            }

                        });

                        this.client.on('error', (err) => {

                            this.sys('Error: ' + (err && err.message ? err.message : err), 'error');

                        });

                        this.client.on('message', (topic, payload, packet) => {

                            this.push({

                                dir: 'in',

                                topic: topic,

                                payload: payload.toString(),

                                retain: !!(packet && packet.retain)

                            });

                        });

                    },

                    disconnect(silent = false) {

                        if (!this.client) return;

                        const c = this.client;

                        this.client = null;

                        try {

                            c.removeAllListeners();

                            c.end(true);

                        } catch (e) {}

                        this.status = 'disconnected';

                        this.subscribed.clear();

                        if (!silent) this.sys('Koneksi diputus.');

                    },

                    validateFilter(filter) {

                        const parts = filter.split('/');

                        for (let i = 0; i < parts.length; i++) {

                            const p = parts[i];

                            if (p.includes('#') && (p !== '#' || i !== parts.length - 1)) {

                                return 'Wildcard # harus berdiri sendiri di level terakhir, contoh: pabrik/#';

                            }

                            if (p.includes('+') && p !== '+') {

                                return 'Wildcard + harus berdiri sendiri di satu level, contoh: pabrik/+/status';

                            }

                        }

                        return null;

                    },

                    addSub() {

                        const t = this.newSub.trim();

                        if (!t) return;

                        const err = this.validateFilter(t);

                        if (err) {

                            this.sys(err, 'error');

                            return;

                        }

                        if (this.subs.includes(t)) {

                            this.sys('Topic sudah ada di daftar: ' + t, 'error');

                            return;

                        }

                        this.subs.push(t);

                        this.newSub = '';

                        this.doSubscribe(t);

                    },

                    removeSub(t) {

                        if (this.client && this.status === 'connected' && this.subscribed.has(t)) {

                            this.client.unsubscribe(t);

                            this.sys('Unsubscribe: ' + t);

                        }

                        this.subscribed.delete(t);

                        this.subs = this.subs.filter((x) => x !== t);

                        if (this.activeFilter === t) this.activeFilter = null;

                    },

                    doSubscribe(t) {

                        if (!this.client || this.status !== 'connected' || this.subscribed.has(t)) return;

                        this.client.subscribe(t, {

                            qos: 0

                        }, (err) => {

                            if (err) {

                                this.sys('Gagal subscribe ' + t + ': ' + err.message, 'error');

                            } else {

                                this.subscribed.add(t);

                                this.sys('Subscribe: ' + t);

                            }

                        });

                    },

                    publish() {

                        const topic = this.pubTopic.trim();

                        const payload = this.pubPayload;

                        if (!topic) {

                            this.sys('Topic tujuan belum diisi.', 'error');

                            return;

                        }

                        if (/[#+]/.test(topic)) {

                            this.sys('Topic publish tidak boleh mengandung wildcard (# atau +).', 'error');

                            return;

                        }

                        if (!this.client || this.status !== 'connected') {

                            this.sys('Belum terhubung ke broker. Klik Connect dulu.', 'error');

                            return;

                        }

                        if (!payload.trim()) {

                            this.sys('Payload masih kosong.', 'error');

                            return;

                        }

                        if (/^\s*[\[{]/.test(payload)) {

                            try {

                                JSON.parse(payload);

                            } catch (e) {

                                this.sys('Peringatan: payload terlihat seperti JSON tetapi tidak valid. Tetap dikirim.');

                            }

                        }

                        const qos = Number(this.pubQos);

                        const retain = this.pubRetain;

                        this.client.publish(topic, payload, {

                            qos: qos,

                            retain: retain

                        }, (err) => {

                            if (err) {

                                this.sys('Gagal publish: ' + err.message, 'error');

                            } else {

                                this.push({

                                    dir: 'out',

                                    topic: topic,

                                    payload: payload,

                                    qos: qos,

                                    retain: retain

                                });

                            }

                        });

                    },

                    push(m) {

                        m.id = this.nextId++;

                        m.time = new Date().toLocaleTimeString('en-GB');

                        this.messages.push(m);

                        if (this.messages.length > this.maxMessages) {

                            this.messages.splice(0, this.messages.length - this.maxMessages);

                        }

                        this.scrollDown();

                    },

                    sys(text, level = 'info') {

                        this.push({

                            dir: 'sys',

                            text: text,

                            level: level

                        });

                    },

                    clearLog() {

                        this.messages = [];

                    },

                    scrollDown() {

                        this.$nextTick(() => {

                            if (this.autoScroll && this.$refs.log) {

                                this.$refs.log.scrollTop = this.$refs.log.scrollHeight;

                            }

                        });

                    },

                    matches(filter, topic) {

                        if (filter === '#') return true;

                        const f = filter.split('/');

                        const t = topic.split('/');

                        for (let i = 0; i < f.length; i++) {

                            if (f[i] === '#') return true;

                            if (i >= t.length) return false;

                            if (f[i] !== '+' && f[i] !== t[i]) return false;

                        }

                        return f.length === t.length;

                    },

                    get visible() {

                        if (!this.activeFilter) return this.messages;

                        return this.messages.filter((m) => m.dir === 'sys' || this.matches(this.activeFilter, m.topic));

                    },

                    get msgCount() {

                        return this.messages.filter((m) => m.dir !== 'sys').length;

                    },

                    get incomingCount() {

                        return this.messages.filter((m) => m.dir === 'in').length;

                    },

                    get outgoingCount() {

                        return this.messages.filter((m) => m.dir === 'out').length;

                    },

                    get statusText() {

                        if (this.status === 'connected') return 'Connected';

                        if (this.status === 'connecting') return 'Connecting...';

                        return 'Disconnected';

                    },

                    pretty(payload) {

                        if (payload.length > 5000) return payload.slice(0, 5000) + '\n... (dipotong)';

                        if (/^\s*[\[{]/.test(payload)) {

                            try {

                                return JSON.stringify(JSON.parse(payload), null, 2);

                            } catch (e) {}

                        }

                        return payload;

                    },

                }));

            };

            if (window.Alpine) {

                register();

            } else {

                document.addEventListener('alpine:init', register);

            }

        })();
    </script>

    <div wire:ignore x-data="mqttInspector" class="space-y-6 text-zinc-950 dark:text-zinc-50">
        {{-- Page header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium"
                    x-bind:class="{
                        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300': status === 'connected',
                        'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300': status === 'connecting',
                        'border-zinc-200 bg-zinc-50 text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400': status === 'disconnected'
                    }">
                    <span class="h-2 w-2 rounded-full" x-bind:class="{
                        'bg-emerald-500': status === 'connected',
                        'bg-amber-500 animate-pulse': status === 'connecting',
                        'bg-zinc-400': status === 'disconnected'
                    }"></span>
                    <span x-text="statusText"></span>
                </span>
                <button type="button" x-on:click="sidebarOpen = true" class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium shadow-sm transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:bg-zinc-800 lg:hidden">
                    <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="h-4 w-4" />
                    Pengaturan
                </button>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Pesan diterima</p>
                    <x-filament::icon icon="heroicon-o-arrow-down-left" class="h-4 w-4 text-zinc-400" />
                </div>
                <p class="mt-2 text-2xl font-semibold tracking-tight tabular-nums" x-text="incomingCount"></p>
                <p class="mt-1 text-xs text-zinc-500">Incoming messages</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Pesan dikirim</p>
                    <x-filament::icon icon="heroicon-o-arrow-up-right" class="h-4 w-4 text-zinc-400" />
                </div>
                <p class="mt-2 text-2xl font-semibold tracking-tight tabular-nums" x-text="outgoingCount"></p>
                <p class="mt-1 text-xs text-zinc-500">Outgoing messages</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Topic subscription</p>
                    <x-filament::icon icon="heroicon-o-radio" class="h-4 w-4 text-zinc-400" />
                </div>
                <p class="mt-2 text-2xl font-semibold tracking-tight tabular-nums" x-text="subs.length"></p>
                <p class="mt-1 text-xs text-zinc-500">Filter yang tersimpan</p>
            </div>
        </div>

        {{-- Main layout --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
            {{-- Mobile overlay --}}
            <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-black/50 backdrop-blur-[2px] lg:hidden" x-on:click="sidebarOpen = false"></div>

            {{-- Sidebar / configuration --}}
            <aside class="fixed inset-y-0 left-0 z-50 w-[min(88vw,360px)] overflow-y-auto border-r border-zinc-200 bg-white p-4 shadow-xl transition-transform duration-200 dark:border-zinc-800 dark:bg-zinc-950 lg:static lg:z-auto lg:col-span-4 lg:w-auto lg:translate-x-0 lg:overflow-visible lg:rounded-xl lg:border lg:p-4 lg:shadow-sm"
                x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
                <div class="mb-4 flex items-center justify-between lg:hidden">
                    <h2 class="text-sm font-semibold">Broker & Topics</h2>
                    <button type="button" x-on:click="sidebarOpen = false" aria-label="Tutup pengaturan" class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-900">
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <section class="space-y-4">
                    <div class="flex items-center gap-2 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                        <x-filament::icon icon="heroicon-o-server-stack" class="h-4 w-4 text-zinc-500" />
                        <div>
                            <h2 class="text-sm font-semibold">Broker settings</h2>
                            <p class="text-xs text-zinc-500">Konfigurasi koneksi WebSocket</p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Broker URL</label>
                        <input type="text" x-model="brokerUrl" x-bind:disabled="status !== 'disconnected'" placeholder="ws://host:9001/mqtt"
                            class="block w-full rounded-md border border-zinc-200 bg-white px-3 py-2 font-mono text-xs shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 disabled:cursor-not-allowed disabled:opacity-60 dark:border-zinc-800 dark:bg-zinc-950 dark:focus:border-zinc-600 dark:focus:ring-zinc-800" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Username <span class="font-normal text-zinc-400">(opsional)</span></label>
                        <input type="text" x-model="username" x-bind:disabled="status !== 'disconnected'" autocomplete="off" placeholder="Username broker"
                            class="block w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 disabled:cursor-not-allowed disabled:opacity-60 dark:border-zinc-800 dark:bg-zinc-950 dark:focus:border-zinc-600 dark:focus:ring-zinc-800" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Password <span class="font-normal text-zinc-400">(opsional, tidak disimpan)</span></label>
                        <input type="password" x-model="password" x-bind:disabled="status !== 'disconnected'" autocomplete="new-password" placeholder="Password broker"
                            class="block w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm outline-none transition placeholder:text-zinc-400 focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 disabled:cursor-not-allowed disabled:opacity-60 dark:border-zinc-800 dark:bg-zinc-950 dark:focus:border-zinc-600 dark:focus:ring-zinc-800" />
                    </div>
                    <button type="button" x-show="status === 'disconnected'" x-on:click="connect()"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white">
                        <x-filament::icon icon="heroicon-o-bolt" class="h-4 w-4" /> Connect broker
                    </button>
                    <button type="button" x-show="status !== 'disconnected'" x-on:click="disconnect()"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-600 shadow-sm transition hover:bg-red-50 dark:border-red-900 dark:bg-zinc-950 dark:text-red-400 dark:hover:bg-red-950/30">
                        <x-filament::icon icon="heroicon-o-stop-circle" class="h-4 w-4" /> Disconnect
                    </button>
                    <p class="rounded-md bg-zinc-50 p-2.5 text-xs leading-relaxed text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">Broker publik dapat diakses siapa saja. Gunakan hanya untuk pengujian, bukan perintah produksi.</p>
                </section>

                <div class="my-5 border-t border-zinc-200 dark:border-zinc-800"></div>

                <section class="space-y-3">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-radio" class="h-4 w-4 text-zinc-500" />
                        <div>
                            <h2 class="text-sm font-semibold">Subscribe topics</h2>
                            <p class="text-xs text-zinc-500">Pilih topic untuk memfilter log</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" x-model="newSub" x-on:keydown.enter.prevent="addSub()" placeholder="pabrik/+/status"
                            class="min-w-0 flex-1 rounded-md border border-zinc-200 bg-white px-3 py-2 font-mono text-xs shadow-sm outline-none placeholder:text-zinc-400 focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-800 dark:bg-zinc-950 dark:focus:border-zinc-600 dark:focus:ring-zinc-800" />
                        <button type="button" x-on:click="addSub()" aria-label="Tambah topic" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-zinc-200 bg-white shadow-sm transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:bg-zinc-900">
                            <x-filament::icon icon="heroicon-o-plus" class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="space-y-1">
                        <button type="button" x-on:click="activeFilter = null; sidebarOpen = false" class="flex w-full items-center justify-between rounded-md px-2.5 py-2 text-left text-sm transition"
                            x-bind:class="activeFilter === null ? 'bg-zinc-100 font-medium text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-900'">
                            <span class="flex items-center gap-2"><x-filament::icon icon="heroicon-o-squares-2x2" class="h-4 w-4" /> Semua pesan</span>
                            <span class="text-xs tabular-nums" x-text="msgCount"></span>
                        </button>
                        <template x-for="t in subs" x-bind:key="t">
                            <div class="group flex items-center gap-1 rounded-md transition" x-bind:class="activeFilter === t ? 'bg-zinc-100 dark:bg-zinc-800' : 'hover:bg-zinc-50 dark:hover:bg-zinc-900'">
                                <button type="button" x-on:click="activeFilter = t; sidebarOpen = false" class="min-w-0 flex-1 truncate px-2.5 py-2 text-left font-mono text-xs"
                                    x-bind:class="activeFilter === t ? 'text-zinc-950 dark:text-white' : 'text-zinc-600 dark:text-zinc-400'">
                                    <span class="mr-1 text-zinc-400">#</span><span x-text="t"></span>
                                </button>
                                <button type="button" x-on:click.stop="removeSub(t)" title="Hapus topic" aria-label="Hapus topic" class="mr-1 rounded p-1.5 text-zinc-400 opacity-70 transition hover:bg-red-50 hover:text-red-600 group-hover:opacity-100 dark:hover:bg-red-950/40 dark:hover:text-red-400">
                                    <x-filament::icon icon="heroicon-o-x-mark" class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </template>
                        <p x-show="subs.length === 0" class="px-2.5 py-3 text-center text-xs text-zinc-400">Belum ada topic.</p>
                    </div>
                </section>
            </aside>

            {{-- Messages and publisher --}}
            <div class="min-w-0 space-y-4 lg:col-span-8">
                <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex flex-col gap-3 border-b border-zinc-200 p-4 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-4 w-4 text-zinc-500" />
                                <h2 class="text-sm font-semibold">Live message log</h2>
                            </div>
                            <p class="mt-1 truncate font-mono text-xs text-zinc-500" x-text="activeFilter || 'Semua topic' "></p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <label class="inline-flex items-center gap-2 text-xs text-zinc-500">
                                <input type="checkbox" x-model="autoScroll" class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-400 dark:border-zinc-700 dark:bg-zinc-950" />
                                Auto-scroll
                            </label>
                            <span class="rounded-md border border-zinc-200 px-2 py-1 font-mono text-xs text-zinc-600 dark:border-zinc-800 dark:text-zinc-300" x-text="msgCount + ' pesan'"></span>
                            <button type="button" x-on:click="clearLog()" class="inline-flex items-center gap-1.5 rounded-md border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-medium shadow-sm transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:bg-zinc-800">
                                <x-filament::icon icon="heroicon-o-trash" class="h-3.5 w-3.5" /> Clear log
                            </button>
                        </div>
                    </div>

                    <div x-ref="log" class="mqtt-scrollbar h-[420px] space-y-4 overflow-y-auto bg-zinc-50/70 p-4 dark:bg-zinc-950/50 sm:h-[520px]">
                        <div x-show="visible.length === 0" class="flex h-full min-h-48 flex-col items-center justify-center text-center">
                            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                                <x-filament::icon icon="heroicon-o-inbox" class="h-5 w-5 text-zinc-400" />
                            </div>
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Belum ada pesan</p>
                            <p class="mt-1 max-w-xs text-xs leading-relaxed text-zinc-500">Hubungkan broker dan subscribe topic untuk mulai menerima pesan.</p>
                        </div>

                        <template x-for="m in visible" x-bind:key="m.id">
                            <div>
                                <template x-if="m.dir === 'sys'">
                                    <div class="flex justify-center py-1">
                                        <span class="max-w-full rounded-full border px-3 py-1 text-center text-[11px]"
                                            x-bind:class="m.level === 'error' ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300' : 'border-zinc-200 bg-white text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400'">
                                            <span x-text="m.time + ' · ' + m.text"></span>
                                        </span>
                                    </div>
                                </template>

                                <template x-if="m.dir === 'in'">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300">
                                            <x-filament::icon icon="heroicon-o-arrow-down-left" class="h-4 w-4" />
                                        </div>
                                        <div class="min-w-0 flex-1 space-y-1.5">
                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px]">
                                                <span class="break-all font-mono font-semibold text-blue-700 dark:text-blue-300" x-text="m.topic"></span>
                                                <span class="text-zinc-400" x-text="m.time"></span>
                                                <span x-show="m.retain" class="rounded border border-zinc-200 px-1.5 py-0.5 text-[10px] text-zinc-500 dark:border-zinc-800">retained</span>
                                            </div>
                                            <pre class="overflow-x-auto whitespace-pre-wrap break-all rounded-lg border border-zinc-200 bg-white p-3 font-mono text-xs leading-relaxed text-zinc-800 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200" x-text="pretty(m.payload)"></pre>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="m.dir === 'out'">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300">
                                            <x-filament::icon icon="heroicon-o-arrow-up-right" class="h-4 w-4" />
                                        </div>
                                        <div class="min-w-0 flex-1 space-y-1.5">
                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px]">
                                                <span class="break-all font-mono font-semibold text-amber-700 dark:text-amber-300" x-text="m.topic"></span>
                                                <span class="text-zinc-400" x-text="m.time"></span>
                                                <span class="rounded border border-zinc-200 px-1.5 py-0.5 text-[10px] text-zinc-500 dark:border-zinc-800" x-text="'QoS ' + m.qos"></span>
                                                <span x-show="m.retain" class="rounded border border-zinc-200 px-1.5 py-0.5 text-[10px] text-zinc-500 dark:border-zinc-800">retain</span>
                                            </div>
                                            <pre class="overflow-x-auto whitespace-pre-wrap break-all rounded-lg border border-amber-200/80 bg-amber-50/60 p-3 text-left font-mono text-xs leading-relaxed text-zinc-800 shadow-sm dark:border-amber-900/70 dark:bg-amber-950/20 dark:text-zinc-200" x-text="pretty(m.payload)"></pre>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </section>

                {{-- Publisher --}}
                <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-paper-airplane" class="h-4 w-4 text-zinc-500" />
                            <h2 class="text-sm font-semibold">Publish message</h2>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500">Kirim payload ke topic tujuan.</p>
                    </div>
                    <div class="space-y-3 p-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Topic tujuan</label>
                            <input type="text" x-model="pubTopic" placeholder="pabrik/sanding-besar/perintah"
                                class="block w-full rounded-md border border-zinc-200 bg-white px-3 py-2 font-mono text-xs shadow-sm outline-none placeholder:text-zinc-400 focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-800 dark:bg-zinc-950 dark:focus:border-zinc-600 dark:focus:ring-zinc-800" />
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Payload</label>
                                <span class="text-[11px] text-zinc-400">Ctrl + Enter untuk mengirim</span>
                            </div>
                            <textarea x-model="pubPayload" rows="4" x-on:keydown.ctrl.enter.prevent="publish()" placeholder='{"command":"START_CYCLE"}'
                                class="block w-full resize-y rounded-md border border-zinc-200 bg-white px-3 py-2.5 font-mono text-xs leading-relaxed shadow-sm outline-none placeholder:text-zinc-400 focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-800 dark:bg-zinc-950 dark:focus:border-zinc-600 dark:focus:ring-zinc-800"></textarea>
                        </div>
                        <div class="flex flex-col gap-3 border-t border-zinc-200 pt-3 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-4">
                                <label class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                                    <span>QoS</span>
                                    <select x-model="pubQos" class="rounded-md border border-zinc-200 bg-white px-2 py-1.5 text-xs shadow-sm outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-800 dark:bg-zinc-950 dark:focus:ring-zinc-800">
                                        <option value="0">0 — At most once</option>
                                        <option value="1">1 — At least once</option>
                                        <option value="2">2 — Exactly once</option>
                                    </select>
                                </label>
                                <label class="inline-flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                                    <input type="checkbox" x-model="pubRetain" class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-400 dark:border-zinc-700 dark:bg-zinc-950" />
                                    Retain message
                                </label>
                            </div>
                            <button type="button" x-on:click="publish()" class="inline-flex items-center justify-center gap-2 rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white">
                                <x-filament::icon icon="heroicon-o-paper-airplane" class="h-4 w-4" /> Send message
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-filament-panels::page>