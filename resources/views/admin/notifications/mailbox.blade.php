<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="mailboxApp()">
        <!-- Header & Action -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                    <a href="{{ route('admin.notifications.email_providers.index') }}" class="hover:text-white transition">Notifications</a>
                    <span>/</span>
                    <span class="text-indigo-400">Hostinger Mailbox Hub</span>
                </div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>📫</span> Live Hostinger Mailbox Inspector
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    Real-time two-way inbox inspector, delivery confirmation, and direct outgoing mail console powered by Hostinger Mail REST API.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button @click="composeModal = true" class="px-4 py-2.5 bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-400 hover:to-indigo-500 text-slate-950 font-black rounded-xl text-xs transition flex items-center gap-2 shadow-lg shadow-cyan-500/20">
                    <span>⚡</span> Compose Email
                </button>
            </div>
        </div>

        <!-- Sub-Nav Links -->
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.notifications.email_providers.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                Email Providers
            </a>
            <a href="{{ route('admin.notifications.mailbox.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-md shadow-indigo-600/20">
                📫 Live Mailbox Inspector
            </a>
            <a href="{{ route('admin.notifications.templates.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                Notification Templates
            </a>
            <a href="{{ route('admin.notifications.failed_queue') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                Failed Critical Queue
            </a>
        </div>

        @if(session('error'))
            <div class="p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-xs text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-xs text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Mailbox Selector Sidebar -->
            <div class="space-y-4 lg:col-span-1">
                <div class="bg-slate-900 rounded-2xl border border-slate-800 p-4 space-y-3">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                        Available Mailboxes
                    </h3>
                    <div class="space-y-1.5">
                        @forelse($mailboxes as $mb)
                            @php
                                $isActive = ($mb['address'] ?? '') === $selectedAddress;
                            @endphp
                            <a href="{{ route('admin.notifications.mailbox.index', ['address' => $mb['address']]) }}" 
                               class="flex items-center justify-between p-2.5 rounded-xl text-xs font-semibold transition {{ $isActive ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-slate-950/60 text-slate-300 hover:bg-slate-800 hover:text-white border border-slate-800/60' }}">
                                <div class="truncate">
                                    <div class="truncate font-mono">{{ $mb['address'] }}</div>
                                    <div class="text-[10px] {{ $isActive ? 'text-indigo-200' : 'text-slate-500' }} font-mono">ID: {{ substr($mb['resourceId'] ?? '', 0, 10) }}...</div>
                                </div>
                                <span class="text-[10px] {{ $isActive ? 'text-white' : 'text-slate-400' }}">→</span>
                            </a>
                        @empty
                            <div class="text-xs text-slate-500 py-2">No mailboxes retrieved.</div>
                        @endforelse
                    </div>
                </div>

                <!-- API Connection Badge -->
                <div class="p-4 bg-emerald-950/30 border border-emerald-500/30 rounded-2xl space-y-1.5">
                    <div class="flex items-center gap-2 text-xs font-bold text-emerald-400">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Hostinger Mail API: Connected</span>
                    </div>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        Authorized token scoped for all account mailboxes. Full read/send access verified.
                    </p>
                </div>
            </div>

            <!-- Messages Table -->
            <div class="lg:col-span-3 space-y-4">
                <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
                    <div class="p-4 border-b border-slate-800/80 flex items-center justify-between">
                        <div>
                            <h2 class="text-xs font-bold text-slate-300 uppercase tracking-wider">
                                INBOX for <span class="text-indigo-400 font-mono">{{ $selectedAddress }}</span>
                            </h2>
                        </div>
                        <span class="text-[11px] text-slate-400 font-mono">
                            {{ count($messages) }} messages loaded
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                                    <th class="py-3 px-3 text-center">UID</th>
                                    <th class="py-3 px-3">From</th>
                                    <th class="py-3 px-4">Subject</th>
                                    <th class="py-3 px-3">Date</th>
                                    <th class="py-3 px-3 text-center">Size</th>
                                    <th class="py-3 px-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                                @forelse($messages as $msg)
                                    <tr class="hover:bg-slate-800/20 transition {{ !empty($msg['unseen']) ? 'bg-indigo-950/20 font-semibold' : '' }}">
                                        <td class="py-3 px-3 text-center font-mono text-slate-500">
                                            #{{ $msg['uid'] }}
                                        </td>
                                        <td class="py-3 px-3 font-mono text-slate-300">
                                            {{ $msg['from']['address'] ?? ($msg['from']['name'] ?? 'Unknown') }}
                                        </td>
                                        <td class="py-3 px-4 font-semibold text-white">
                                            <div class="truncate max-w-xs sm:max-w-md">
                                                {{ $msg['subject'] ?: '(No Subject)' }}
                                            </div>
                                        </td>
                                        <td class="py-3 px-3 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                            {{ isset($msg['date']) ? \Carbon\Carbon::parse($msg['date'])->diffForHumans() : '—' }}
                                        </td>
                                        <td class="py-3 px-3 text-center text-slate-400 font-mono text-[11px]">
                                            {{ isset($msg['size']) ? number_format($msg['size'] / 1024, 1) . ' KB' : '—' }}
                                        </td>
                                        <td class="py-3 px-3 text-right">
                                            <button type="button" @click="viewMessage('{{ $selectedAddress }}', {{ $msg['uid'] }}, '{{ addslashes($msg['subject'] ?? '') }}', '{{ addslashes($msg['from']['address'] ?? '') }}')" class="px-2.5 py-1 bg-indigo-600/30 hover:bg-indigo-600 text-indigo-300 hover:text-white rounded-lg text-xs font-bold transition">
                                                🔍 Read Content
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-500 font-sans">
                                            <div class="text-2xl mb-1">📭</div>
                                            <div>No messages found in this mailbox.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Read Content Modal -->
        <div x-show="viewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="viewModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-3xl w-full shadow-2xl space-y-4 max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="truncate pr-4">
                        <div class="text-xs font-bold text-indigo-400 font-mono" x-text="'From: ' + currentFrom"></div>
                        <h3 class="text-base font-bold text-white truncate" x-text="currentSubject"></h3>
                    </div>
                    <button @click="viewModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>

                <div class="flex-1 overflow-y-auto space-y-3">
                    <div x-show="loadingContent" class="py-12 text-center text-slate-400">
                        <div class="animate-spin text-2xl mb-2">⚡</div>
                        <div class="text-xs">Loading email content from Hostinger API...</div>
                    </div>

                    <div x-show="!loadingContent" class="space-y-3">
                        <div class="bg-slate-950 rounded-2xl p-4 border border-slate-800" x-show="currentHtml">
                            <div class="text-[10px] uppercase font-bold text-slate-400 mb-2">Rendered HTML View:</div>
                            <div class="p-4 bg-slate-900 rounded-xl text-slate-200 text-xs overflow-x-auto" x-html="currentHtml"></div>
                        </div>

                        <div class="bg-slate-950 rounded-2xl p-4 border border-slate-800">
                            <div class="text-[10px] uppercase font-bold text-slate-400 mb-2">Plain Text Content:</div>
                            <pre class="text-xs font-mono text-slate-300 whitespace-pre-wrap" x-text="currentText"></pre>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-800 pt-3 flex justify-end">
                    <button @click="viewModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition">
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Compose Modal -->
        <div x-show="composeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="composeModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">⚡ Send Outbound Email (Hostinger API)</h3>
                    <button @click="composeModal = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>

                <form method="POST" action="{{ route('admin.notifications.mailbox.send') }}" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">From Mailbox</label>
                        <select name="from_address" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500 font-mono">
                            @foreach($mailboxes as $mb)
                                <option value="{{ $mb['address'] }}" {{ $mb['address'] === $selectedAddress ? 'selected' : '' }}>{{ $mb['address'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Recipient Email</label>
                        <input type="email" name="to" required placeholder="user@example.com" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">Subject</label>
                        <input type="text" name="subject" required placeholder="Important Notice Regarding Your Billing Cycle" class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-slate-400 font-bold uppercase text-[10px] mb-1">HTML / Text Message Body</label>
                        <textarea name="body" rows="5" required placeholder="Enter message content..." class="w-full bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 focus:ring-indigo-500 font-mono"></textarea>
                    </div>

                    <div class="pt-2 flex justify-end gap-2 border-t border-slate-800">
                        <button type="button" @click="composeModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-indigo-600 text-slate-950 font-black rounded-xl text-xs transition shadow-md shadow-cyan-500/20">🚀 Send via Hostinger</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function mailboxApp() {
            return {
                viewModal: false,
                composeModal: false,
                loadingContent: false,
                currentSubject: '',
                currentFrom: '',
                currentHtml: '',
                currentText: '',

                viewMessage(address, uid, subject, from) {
                    this.viewModal = true;
                    this.loadingContent = true;
                    this.currentSubject = subject;
                    this.currentFrom = from;
                    this.currentHtml = '';
                    this.currentText = '';

                    fetch(`/admin/notifications/mailbox/${uid}/content?address=${encodeURIComponent(address)}`)
                        .then(res => res.json())
                        .then(data => {
                            this.currentHtml = data.html || '';
                            this.currentText = data.text || '';
                            this.loadingContent = false;
                        })
                        .catch(err => {
                            alert('Failed to load message content: ' + err);
                            this.loadingContent = false;
                        });
                }
            };
        }
    </script>
</x-admin-layout>
