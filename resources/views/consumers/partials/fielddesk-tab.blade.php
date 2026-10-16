{{-- FieldDesk Commitments & Action History Tab --}}
<div x-show="activeTab === 'fielddesk'" class="space-y-6">
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <span class="text-xl">📋</span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Field Follow-ups & Payment Promises ({{ $fieldDeskActions->count() }})</h3>
            </div>
            <a href="{{ route('field-desk.index', ['ca' => $account->ca_number]) }}" class="text-xs font-bold text-brand-600 dark:text-cyan-400 hover:underline flex items-center gap-1">
                <span>↗️</span> Open in FieldDesk Hub
            </a>
        </div>

        @if($fieldDeskActions->isEmpty())
            <div class="py-8 text-center text-xs text-slate-500">
                No FieldDesk follow-ups or payment promises recorded for this consumer yet.
            </div>
        @else
            <div class="space-y-3">
                @foreach($fieldDeskActions as $action)
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-500 border border-indigo-500/20">
                                    {{ $action->category?->name ?? 'General Note' }}
                                </span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $action->status === 'resolved' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500' }}">
                                    {{ ucfirst($action->status) }}
                                </span>
                                <span class="text-slate-400 font-mono text-[11px]">Due: {{ $action->target_date ? $action->target_date->format('d M Y') : 'Immediate' }}</span>
                            </div>
                            <div class="text-slate-700 dark:text-slate-300 font-medium mt-1">
                                {{ $action->private_note ?: 'No specific note added.' }}
                            </div>
                            @if($action->target_amount)
                                <div class="text-[11px] font-mono text-slate-500 mt-0.5">
                                    Promise Amount: <strong class="text-slate-900 dark:text-white">₹{{ number_format((float)$action->target_amount, 2) }}</strong>
                                    @if($action->collected_amount)
                                        • Collected: <strong class="text-emerald-500">₹{{ number_format((float)$action->collected_amount, 2) }}</strong>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 self-start sm:self-center">
                            @if($account->mobile)
                                <a href="https://wa.me/91{{ preg_replace('/[^0-9]/', '', $account->mobile) }}" target="_blank" rel="noopener" class="px-2.5 py-1 rounded-lg bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-600 dark:text-emerald-400 border border-emerald-600/20 font-bold transition flex items-center gap-1">
                                    <span>💬</span> WhatsApp
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
