{{-- Messages Table --}}
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
