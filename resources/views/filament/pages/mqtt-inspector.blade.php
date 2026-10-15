{{-- MQTT Inspector — Shadcn-inspired Laravel Filament --}}
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

        /* Animasi pesan baru */
        @keyframes mqtt-message-in {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes mqtt-message-out {
            from {
                opacity: 0;
                transform: translate(12px, 6px);
            }

            to {
                opacity: 1;
                transform: translate(0, 0);
            }
        }

        @keyframes mqtt-system-in {
            from {
                opacity: 0;
                transform: scale(.97);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .mqtt-message-in {
            animation: mqtt-message-in 260ms cubic-bezier(.16, 1, .3, 1) both;
        }

        .mqtt-message-out {
            animation: mqtt-message-out 260ms cubic-bezier(.16, 1, .3, 1) both;
        }

        .mqtt-system-message {
            animation: mqtt-system-in 180ms ease-out both;
        }

        .mqtt-bubble {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .mqtt-payload {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
            max-height: 360px;
            overflow-y: auto;
        }

        .mqtt-payload-collapsed {
            max-height: 120px;
            overflow: hidden;
        }

        @media (prefers-reduced-motion: reduce) {

            .mqtt-message-in,
            .mqtt-message-out,
            .mqtt-system-message {
                animation: none !important;
            }
        }
    </style>

    <script>
        (() => {
            const register = () => {
                if (Alpine.data('mqttInspector')) return;

                Alpine.data('mqttInspector', () => ({
                    // Connection
                    brokerUrl: 'ws://broker.hivemq.com:8000/mqtt',
                    username: '',
                    password: '',
                    status: 'disconnected',
                    client: null,
                    subscribed: new Set(),

                    // Subscriptions
                    subs: ['pabrik/#'],
                    newSub: '',
                    activeFilter: null,

                    // Publisher
                    pubTopic: 'pabrik/sanding-besar/perintah',
                    pubPayload: '{\n  "command": "START_CYCLE"\n}',
                    pubQos: 0,
                    pubRetain: false,

                    // Logs
                    messages: [],
                    nextId: 1,
                    maxMessages: 500,
                    autoScroll: true,
                    sidebarOpen: false,
                    expanded: {},
                    connectedOnce: false,

                    init() {
                        this.loadSettings();

                        [
                            'brokerUrl',
                            'username',
                            'subs',
                            'pubTopic',
                            'pubPayload',
                            'pubQos',
                            'pubRetain',
                        ].forEach(key => {
                            this.$watch(key, () => this.saveSettings());
                        });
                    },

                    destroy() {
                        this.disconnect(true);
                    },

                    loadSettings() {
                        try {
                            const settings = JSON.parse(
                                localStorage.getItem('nexora.mqtt.inspector') || '{}'
                            );

                            if (settings.brokerUrl) this.brokerUrl = settings.brokerUrl;
                            if (typeof settings.username === 'string') {
                                this.username = settings.username;
                            }
                            if (Array.isArray(settings.subs)) {
                                this.subs = settings.subs;
                            }
                            if (settings.pubTopic) this.pubTopic = settings.pubTopic;
                            if (typeof settings.pubPayload === 'string') {
                                this.pubPayload = settings.pubPayload;
                            }
                            if (settings.pubQos !== undefined) {
                                this.pubQos = settings.pubQos;
                            }
                            if (typeof settings.pubRetain === 'boolean') {
                                this.pubRetain = settings.pubRetain;
                            }
                        } catch (e) {
                            // Ignore invalid local settings.
                        }
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
                        } catch (e) {
                            // Storage may be unavailable.
                        }
                    },

                    connect() {
                        if (this.status !== 'disconnected') return;

                        if (typeof mqtt === 'undefined') {
                            this.sys(
                                'Library mqtt.js belum termuat. Muat ulang halaman lalu coba lagi.',
                                'error'
                            );
                            return;
                        }

                        const url = this.brokerUrl.trim();

                        if (!/^wss?:\/\//i.test(url)) {
                            this.sys('Broker URL harus diawali ws:// atau wss://', 'error');
                            return;
                        }

                        if (location.protocol === 'https:' && /^ws:\/\//i.test(url)) {
                            this.sys(
                                'Halaman menggunakan HTTPS. Gunakan broker wss://.',
                                'error'
                            );
                            return;
                        }

                        const options = {
                            clientId: 'nexora-inspector-' +
                                Math.random().toString(16).slice(2, 10),
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

                        const client = this.client;

                        client.on('connect', () => {
                            if (this.client !== client) return;

                            this.status = 'connected';
                            this.connectedOnce = true;
                            this.sys('Terhubung ke broker.');

                            this.subscribed.clear();
                            this.subs.forEach(topic => this.doSubscribe(topic));
                        });

                        client.on('reconnect', () => {
                            if (this.client === client) {
                                this.status = 'connecting';
                            }
                        });

                        client.on('close', () => {
                            if (this.client !== client) return;

                            this.subscribed.clear();

                            if (this.status === 'connected') {
                                this.status = 'connecting';
                                this.sys(
                                    'Koneksi terputus. Mencoba menyambung ulang...',
                                    'error'
                                );
                            }
                        });

                        client.on('error', err => {
                            if (this.client !== client) return;

                            this.sys(
                                'Error: ' + (err?.message || String(err)),
                                'error'
                            );
                        });

                        client.on('message', (topic, payload, packet) => {
                            if (this.client !== client) return;

                            this.push({
                                dir: 'in',
                                topic,
                                payload: payload.toString(),
                                retain: !!(packet && packet.retain),
                                qos: packet?.qos ?? 0,
                            });
                        });
                    },

                    disconnect(silent = false) {
                        const client = this.client;

                        if (!client) {
                            this.status = 'disconnected';
                            return;
                        }

                        this.client = null;
                        this.status = 'disconnected';
                        this.subscribed.clear();

                        try {
                            client.removeAllListeners();
                            client.end(true);
                        } catch (e) {
                            // Ignore teardown errors.
                        }

                        if (!silent) this.sys('Koneksi diputus.');
                    },

                    validateFilter(filter) {
                        if (!filter) return 'Topic filter tidak boleh kosong.';

                        const parts = filter.split('/');

                        for (let i = 0; i < parts.length; i++) {
                            const part = parts[i];

                            if (part.includes('#') &&
                                (part !== '#' || i !== parts.length - 1)) {
                                return 'Wildcard # harus berdiri sendiri di level terakhir.';
                            }

                            if (part.includes('+') && part !== '+') {
                                return 'Wildcard + harus berdiri sendiri dalam satu level.';
                            }
                        }

                        return null;
                    },

                    addSub() {
                        const topic = this.newSub.trim();
                        if (!topic) return;

                        const error = this.validateFilter(topic);

                        if (error) {
                            this.sys(error, 'error');
                            return;
                        }

                        if (this.subs.includes(topic)) {
                            this.sys('Topic sudah ada: ' + topic, 'error');
                            return;
                        }

                        this.subs.push(topic);
                        this.newSub = '';
                        this.doSubscribe(topic);
                    },

                    removeSub(topic) {
                        if (this.client &&
                            this.status === 'connected' &&
                            this.subscribed.has(topic)) {
                            this.client.unsubscribe(topic);
                            this.sys('Unsubscribe: ' + topic);
                        }

                        this.subscribed.delete(topic);
                        this.subs = this.subs.filter(item => item !== topic);

                        if (this.activeFilter === topic) {
                            this.activeFilter = null;
                        }
                    },

                    doSubscribe(topic) {
                        if (!this.client ||
                            this.status !== 'connected' ||
                            this.subscribed.has(topic)) return;

                        const client = this.client;

                        client.subscribe(topic, {
                            qos: 0
                        }, err => {
                            if (this.client !== client) return;

                            if (err) {
                                this.sys(
                                    'Gagal subscribe ' + topic + ': ' + err.message,
                                    'error'
                                );
                            } else {
                                this.subscribed.add(topic);
                                this.sys('Subscribe: ' + topic);
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

                        if (/[+#]/.test(topic)) {
                            this.sys(
                                'Topic publish tidak boleh mengandung wildcard # atau +.',
                                'error'
                            );
                            return;
                        }

                        if (!this.client || this.status !== 'connected') {
                            this.sys(
                                'Belum terhubung ke broker. Klik Connect dulu.',
                                'error'
                            );
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
                                this.sys(
                                    'Peringatan: payload terlihat seperti JSON tetapi tidak valid.',
                                    'error'
                                );
                                return;
                            }
                        }

                        const qos = Number(this.pubQos);
                        const retain = this.pubRetain;
                        const client = this.client;

                        client.publish(topic, payload, {
                            qos,
                            retain
                        }, err => {
                            if (this.client !== client) return;

                            if (err) {
                                this.sys('Gagal publish: ' + err.message, 'error');
                                return;
                            }

                            this.push({
                                dir: 'out',
                                topic,
                                payload,
                                qos,
                                retain,
                            });
                        });
                    },

                    push(message) {
                        message.id = this.nextId++;
                        message.time = new Date().toLocaleTimeString('id-ID', {
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                            hour12: false,
                        });

                        this.messages.push(message);

                        if (this.messages.length > this.maxMessages) {
                            const count = this.messages.length - this.maxMessages;
                            this.messages.splice(0, count);
                        }

                        this.scrollDown();
                    },

                    sys(message, level = 'info') {
                        this.push({
                            dir: 'sys',
                            text: message,
                            level,
                        });
                    },

                    clearLog() {
                        this.messages = [];
                        this.expanded = {};
                    },

                    scrollDown() {
                        this.$nextTick(() => {
                            if (this.autoScroll && this.$refs.log) {
                                this.$refs.log.scrollTo({
                                    top: this.$refs.log.scrollHeight,
                                    behavior: window.matchMedia(
                                        '(prefers-reduced-motion: reduce)'
                                    ).matches ? 'auto' : 'smooth',
                                });
                            }
                        });
                    },

                    matches(filter, topic) {
                        if (!filter) return true;

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

                        return this.messages.filter(message =>
                            message.dir === 'sys' ||
                            this.matches(this.activeFilter, message.topic || '')
                        );
                    },

                    get msgCount() {
                        return this.messages.filter(m => m.dir !== 'sys').length;
                    },

                    get incomingCount() {
                        return this.messages.filter(m => m.dir === 'in').length;
                    },

                    get outgoingCount() {
                        return this.messages.filter(m => m.dir === 'out').length;
                    },

                    get statusText() {
                        if (this.status === 'connected') return 'Connected';
                        if (this.status === 'connecting') return 'Connecting...';
                        return 'Disconnected';
                    },

                    pretty(payload) {
                        if (payload.length > 5000) {
                            payload = payload.slice(0, 5000) + '\n... (dipotong)';
                        }

                        if (/^\s*[\[{]/.test(payload)) {
                            try {
                                return JSON.stringify(JSON.parse(payload), null, 2);
                            } catch (e) {
                                // Show raw payload when JSON is invalid.
                            }
                        }

                        return payload;
                    },

                    toggleExpanded(id) {
                        this.expanded[id] = !this.expanded[id];
                    },

                    isJson(payload) {
                        try {
                            const parsed = JSON.parse(payload);
                            return parsed !== null && typeof parsed === 'object';
                        } catch (e) {
                            return false;
                        }
                    },

                    formatPayload(payload) {
                        return this.pretty(payload);
                    },
                }));
            };

            if (window.Alpine) {
                register();
            } else {
                document.addEventListener('alpine:init', register, {
                    once: true
                });
            }
        })();
    </script>

    <div wire:ignore x-data="mqttInspector"
        class="space-y-5 text-zinc-950 dark:text-zinc-50">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <x-filament::icon icon="heroicon-o-chat-bubble-left-right"
                        class="h-5 w-5 text-zinc-700 dark:text-zinc-200" />
                </div>
                <div>
                    <h2 class="text-base font-semibold tracking-tight">MQTT Inspector</h2>
                    <p class="text-xs text-zinc-500">Live message monitoring</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium"
                    :class="{
                          'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300': status === 'connected',
                          'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300': status === 'connecting',
                          'border-zinc-200 bg-white text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400': status === 'disconnected'
                      }">
                    <span class="h-2 w-2 rounded-full"
                        :class="{
                              'bg-emerald-500': status === 'connected',
                              'bg-amber-500 animate-pulse': status === 'connecting',
                              'bg-zinc-400': status === 'disconnected'
                          }"></span>
                    <span x-text="statusText"></span>
                </span>

                <button type="button" @click="sidebarOpen = true"
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 lg:hidden">
                    <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="h-4 w-4" />
                    Pengaturan
                </button>
            </div>
        </div>

        {{-- Summary --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <template x-for="stat in [
                { label: 'Pesan diterima', value: incomingCount, icon: 'heroicon-o-arrow-down-left', color: 'text-blue-600 dark:text-blue-400' },
                { label: 'Pesan dikirim', value: outgoingCount, icon: 'heroicon-o-arrow-up-right', color: 'text-amber-600 dark:text-amber-400' },
                { label: 'Subscription', value: subs.length, icon: 'heroicon-o-radio', color: 'text-zinc-500' }
            ]" :key="stat.label">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-zinc-500" x-text="stat.label"></p>
                        <x-filament::icon icon="heroicon-o-chart-bar" class="h-4 w-4 text-zinc-400" />
                    </div>
                    <p class="mt-2 text-2xl font-semibold tracking-tight tabular-nums"
                        x-text="stat.value"></p>
                </div>
            </template>
        </div>

        {{-- Main layout --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">

            {{-- Mobile overlay --}}
            <div x-cloak x-show="sidebarOpen" x-transition.opacity
                class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"
                @click="sidebarOpen = false"></div>

            {{-- Sidebar --}}
            <aside class="fixed inset-y-0 left-0 z-50 w-[min(88vw,360px)] overflow-y-auto border-r border-zinc-200 bg-white p-4 shadow-xl transition-transform duration-200 dark:border-zinc-800 dark:bg-zinc-950 lg:static lg:z-auto lg:col-span-4 lg:w-auto lg:translate-x-0 lg:rounded-xl lg:border lg:shadow-sm"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

                <div class="mb-5 flex items-center justify-between lg:hidden">
                    <h2 class="text-sm font-semibold">Broker & Topics</h2>
                    <button type="button" @click="sidebarOpen = false"
                        class="rounded-lg p-2 hover:bg-zinc-100 dark:hover:bg-zinc-900"
                        aria-label="Tutup pengaturan">
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                    </button>
                </div>

                {{-- Broker --}}
                <section class="space-y-4">
                    <div class="flex items-center gap-2 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                        <x-filament::icon icon="heroicon-o-server-stack" class="h-4 w-4 text-zinc-500" />
                        <div>
                            <h3 class="text-sm font-semibold">Broker settings</h3>
                            <p class="text-xs text-zinc-500">Konfigurasi WebSocket</p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-medium">Broker URL</label>
                        <input type="text" x-model="brokerUrl"
                            :disabled="status !== 'disconnected'"
                            placeholder="ws://host:9001/mqtt"
                            class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 font-mono text-xs outline-none focus:ring-2 focus:ring-zinc-200 disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:focus:ring-zinc-800" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-medium">Username <span class="text-zinc-400">(opsional)</span></label>
                        <input type="text" x-model="username"
                            :disabled="status !== 'disconnected'"
                            autocomplete="off" placeholder="Username broker"
                            class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-zinc-200 disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:focus:ring-zinc-800" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-medium">Password <span class="text-zinc-400">(tidak disimpan)</span></label>
                        <input type="password" x-model="password"
                            :disabled="status !== 'disconnected'"
                            autocomplete="new-password" placeholder="Password broker"
                            class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-zinc-200 disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-950 dark:focus:ring-zinc-800" />
                    </div>

                    <button x-show="status === 'disconnected'" type="button" @click="connect()"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-zinc-900 px-3 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white">
                        <x-filament::icon icon="heroicon-o-bolt" class="h-4 w-4" />
                        Connect broker
                    </button>

                    <button x-show="status !== 'disconnected'" type="button" @click="disconnect()"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-900 dark:bg-zinc-950 dark:text-red-400">
                        <x-filament::icon icon="heroicon-o-stop-circle" class="h-4 w-4" />
                        Disconnect
                    </button>

                    <p class="rounded-lg bg-zinc-50 p-3 text-xs leading-relaxed text-zinc-500 dark:bg-zinc-900">
                        Broker publik dapat diakses siapa saja. Jangan gunakan untuk perintah produksi.
                    </p>
                </section>

                <div class="my-5 border-t border-zinc-200 dark:border-zinc-800"></div>

                {{-- Subscribe --}}
                <section class="space-y-3">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-radio" class="h-4 w-4 text-zinc-500" />
                        <div>
                            <h3 class="text-sm font-semibold">Subscribe topics</h3>
                            <p class="text-xs text-zinc-500">Filter log berdasarkan topic</p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <input type="text" x-model="newSub"
                            @keydown.enter.prevent="addSub()"
                            placeholder="pabrik/+/status"
                            class="min-w-0 flex-1 rounded-lg border border-zinc-200 bg-white px-3 py-2 font-mono text-xs outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-800 dark:bg-zinc-950" />
                        <button type="button" @click="addSub()"
                            aria-label="Tambah topic"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950">
                            <x-filament::icon icon="heroicon-o-plus" class="h-4 w-4" />
                        </button>
                    </div>

                    <button type="button"
                        @click="activeFilter = null; sidebarOpen = false"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm"
                        :class="activeFilter === null ? 'bg-zinc-100 font-medium dark:bg-zinc-800' : 'text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-900'">
                        <span class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-squares-2x2" class="h-4 w-4" />
                            Semua pesan
                        </span>
                        <span class="text-xs tabular-nums" x-text="msgCount"></span>
                    </button>

                    <div class="space-y-1">
                        <template x-for="topic in subs" :key="topic">
                            <div class="group flex items-center rounded-lg"
                                :class="activeFilter === topic ? 'bg-zinc-100 dark:bg-zinc-800' : 'hover:bg-zinc-50 dark:hover:bg-zinc-900'">
                                <button type="button"
                                    @click="activeFilter = topic; sidebarOpen = false"
                                    class="min-w-0 flex-1 truncate px-3 py-2.5 text-left font-mono text-xs">
                                    <span class="mr-1 text-zinc-400">#</span>
                                    <span x-text="topic"></span>
                                </button>
                                <button type="button" @click.stop="removeSub(topic)"
                                    aria-label="Hapus topic"
                                    class="mr-1 rounded p-1.5 text-zinc-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40">
                                    <x-filament::icon icon="heroicon-o-x-mark" class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </template>
                        <p x-show="subs.length === 0"
                            class="py-4 text-center text-xs text-zinc-400">Belum ada topic.</p>
                    </div>
                </section>
            </aside>

            {{-- Message log + publisher --}}
            <div class="min-w-0 space-y-4 lg:col-span-8">

                {{-- Live messages --}}
                <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 p-4 dark:border-zinc-800">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-4 w-4 text-zinc-500" />
                                <h3 class="text-sm font-semibold">Live message log</h3>
                            </div>
                            <p class="mt-1 truncate font-mono text-xs text-zinc-500"
                                x-text="activeFilter || 'Semua topic'"></p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <label class="inline-flex items-center gap-2 text-xs text-zinc-500">
                                <input type="checkbox" x-model="autoScroll"
                                    class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-400" />
                                Auto-scroll
                            </label>
                            <span class="rounded-md border border-zinc-200 px-2 py-1 font-mono text-xs text-zinc-500 dark:border-zinc-800"
                                x-text="msgCount + ' pesan'"></span>
                            <button type="button" @click="clearLog()"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-2.5 py-1.5 text-xs font-medium hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800">
                                <x-filament::icon icon="heroicon-o-trash" class="h-3.5 w-3.5" />
                                Clear
                            </button>
                        </div>
                    </div>

                    <div x-ref="log"
                        class="mqtt-scrollbar h-[520px] space-y-5 overflow-y-auto bg-zinc-50/70 p-4 dark:bg-zinc-950/50 sm:p-5">

                        {{-- Empty state --}}
                        <div x-show="visible.length === 0"
                            class="flex h-full min-h-48 flex-col items-center justify-center text-center">
                            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                                <x-filament::icon icon="heroicon-o-inbox" class="h-6 w-6 text-zinc-400" />
                            </div>
                            <p class="text-sm font-medium">Belum ada pesan</p>
                            <p class="mt-1 max-w-xs text-xs leading-relaxed text-zinc-500">
                                Hubungkan broker dan subscribe topic untuk mulai menerima pesan.
                            </p>
                        </div>

                        <template x-for="m in visible" :key="m.id">
                            <div>
                                {{-- SYSTEM MESSAGE --}}
                                <template x-if="m.dir === 'sys'">
                                    <div class="mqtt-system-message flex justify-center py-1">
                                        <div class="flex max-w-full items-start gap-2 rounded-xl border px-3 py-2 text-xs shadow-sm"
                                            :class="m.level === 'error'
                                                ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300'
                                                : 'border-zinc-200 bg-white text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400'">
                                            <template x-if="m.level === 'error'">
                                                <x-filament::icon icon="heroicon-o-exclamation-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                                            </template>
                                            <template x-if="m.level !== 'error'">
                                                <x-filament::icon icon="heroicon-o-information-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                                            </template>
                                            <div class="min-w-0">
                                                <p class="font-medium"
                                                    x-text="m.level === 'error' ? 'System error' : 'System'"></p>
                                                <p class="mt-0.5 break-words leading-relaxed" x-text="m.text"></p>
                                                <p class="mt-1 text-[10px] opacity-70" x-text="m.time"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                {{-- INCOMING MESSAGE --}}
                                <template x-if="m.dir === 'in'">
                                    <div class="mqtt-message-in flex items-start gap-2.5 sm:gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/50 dark:text-blue-300">
                                            <x-filament::icon icon="heroicon-o-arrow-down-left" class="h-4 w-4" />
                                        </div>

                                        <div class="min-w-0 max-w-[88%] flex-1 sm:max-w-[82%]">
                                            <div class="mb-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px]">
                                                <span class="font-semibold text-blue-700 dark:text-blue-300">Incoming</span>
                                                <span class="text-zinc-400" x-text="m.time"></span>
                                                <span x-show="m.retain"
                                                    class="rounded border border-blue-200 px-1.5 py-0.5 text-[10px] text-blue-600 dark:border-blue-900 dark:text-blue-300">retained</span>
                                            </div>

                                            <div class="mqtt-bubble overflow-hidden rounded-2xl rounded-tl-md border border-blue-200/80 bg-white shadow-sm dark:border-blue-900/60 dark:bg-zinc-900">
                                                <div class="border-b border-blue-100/80 bg-blue-50/70 px-3 py-2 dark:border-blue-900/50 dark:bg-blue-950/30">
                                                    <p class="break-all font-mono text-[11px] font-medium text-blue-800 dark:text-blue-200"
                                                        x-text="m.topic"></p>
                                                </div>

                                                <div class="p-3">
                                                    <pre class="mqtt-payload mqtt-scrollbar font-mono text-xs leading-relaxed text-zinc-800 dark:text-zinc-200"
                                                        :class="expanded[m.id] ? '' : 'mqtt-payload-collapsed'"
                                                        x-text="formatPayload(m.payload)"></pre>

                                                    <div class="mt-2 flex items-center justify-between gap-2 border-t border-zinc-100 pt-2 dark:border-zinc-800">
                                                        <span class="text-[10px] text-zinc-400"
                                                            x-text="'QoS ' + (m.qos ?? 0)"></span>
                                                        <button type="button"
                                                            @click="toggleExpanded(m.id)"
                                                            class="text-[11px] font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400"
                                                            x-text="expanded[m.id] ? 'Tampilkan lebih sedikit' : 'Lihat payload'">
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                {{-- OUTGOING MESSAGE --}}
                                <template x-if="m.dir === 'out'">
                                    <div class="mqtt-message-out flex items-start justify-end gap-2.5 sm:gap-3">
                                        <div class="min-w-0 max-w-[88%] sm:max-w-[82%]">
                                            <div class="mb-1.5 flex flex-wrap items-center justify-end gap-x-2 gap-y-1 text-[11px]">
                                                <span x-show="m.retain"
                                                    class="rounded border border-amber-200 px-1.5 py-0.5 text-[10px] text-amber-700 dark:border-amber-900 dark:text-amber-300">retain</span>
                                                <span class="text-zinc-400" x-text="'QoS ' + m.qos"></span>
                                                <span class="text-zinc-400" x-text="m.time"></span>
                                                <span class="font-semibold text-amber-700 dark:text-amber-300">Outgoing · You</span>
                                            </div>

                                            <div class="mqtt-bubble overflow-hidden rounded-2xl rounded-tr-md border border-amber-200/80 bg-amber-50/70 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/20">
                                                <div class="border-b border-amber-200/60 px-3 py-2 dark:border-amber-900/50">
                                                    <p class="break-all font-mono text-[11px] font-medium text-amber-800 dark:text-amber-200"
                                                        x-text="m.topic"></p>
                                                </div>

                                                <div class="p-3">
                                                    <pre class="mqtt-payload mqtt-scrollbar font-mono text-xs leading-relaxed text-zinc-800 dark:text-zinc-200"
                                                        :class="expanded[m.id] ? '' : 'mqtt-payload-collapsed'"
                                                        x-text="formatPayload(m.payload)"></pre>

                                                    <div class="mt-2 flex justify-end border-t border-amber-200/60 pt-2 dark:border-amber-900/50">
                                                        <button type="button"
                                                            @click="toggleExpanded(m.id)"
                                                            class="text-[11px] font-medium text-amber-700 hover:text-amber-900 dark:text-amber-300"
                                                            x-text="expanded[m.id] ? 'Tampilkan lebih sedikit' : 'Lihat payload'">
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300">
                                            <x-filament::icon icon="heroicon-o-arrow-up-right" class="h-4 w-4" />
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-zinc-200 px-4 py-2.5 dark:border-zinc-800">
                        <p class="text-[11px] text-zinc-400">
                            Menampilkan maksimal 500 pesan terbaru
                        </p>
                        <p class="text-[11px] text-zinc-400">
                            <span x-text="incomingCount"></span> masuk
                            · <span x-text="outgoingCount"></span> keluar
                        </p>
                    </div>
                </section>

                {{-- Publisher --}}
                <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300">
                            <x-filament::icon icon="heroicon-o-paper-airplane" class="h-4 w-4" />
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold">Publish message</h3>
                            <p class="text-xs text-zinc-500">Kirim pesan ke broker MQTT</p>
                        </div>
                    </div>

                    <div class="space-y-4 p-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-medium">Topic tujuan</label>
                            <input type="text" x-model="pubTopic"
                                placeholder="pabrik/sanding-besar/perintah"
                                class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 font-mono text-xs outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-800 dark:bg-zinc-950" />
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="text-xs font-medium">Payload</label>
                                <span class="text-[11px] text-zinc-400">Ctrl + Enter untuk mengirim</span>
                            </div>
                            <textarea x-model="pubPayload" rows="5"
                                @keydown.ctrl.enter.prevent="publish()"
                                placeholder='{"command":"START_CYCLE"}'
                                class="w-full resize-y rounded-lg border border-zinc-200 bg-white px-3 py-2.5 font-mono text-xs leading-relaxed outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-800 dark:bg-zinc-950"></textarea>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-zinc-200 pt-3 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-4">
                                <label class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                                    QoS
                                    <select x-model="pubQos"
                                        class="rounded-lg border border-zinc-200 bg-white px-2 py-1.5 text-xs dark:border-zinc-800 dark:bg-zinc-950">
                                        <option value="0">0 — At most once</option>
                                        <option value="1">1 — At least once</option>
                                        <option value="2">2 — Exactly once</option>
                                    </select>
                                </label>

                                <label class="inline-flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                                    <input type="checkbox" x-model="pubRetain"
                                        class="rounded border-zinc-300 text-amber-600 focus:ring-amber-500" />
                                    Retain message
                                </label>
                            </div>

                            <button type="button" @click="publish()"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white">
                                <x-filament::icon icon="heroicon-o-paper-airplane" class="h-4 w-4" />
                                Send message
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-filament-panels::page>