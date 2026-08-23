<x-app-layout>
    <div class="space-y-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>⚙️</span> Notification Preferences
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    Control which channels are used to deliver billing, wallet, and report notifications.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('notifications.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-2">
                    <span>←</span> Back to Notifications
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-xs text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="p-4 bg-indigo-950/20 border border-indigo-500/30 rounded-2xl text-xs text-indigo-300 flex items-start gap-3">
            <span class="text-base">ℹ️</span>
            <div>
                <strong>Important Policy:</strong> In-App Dashboard alerts for <span class="text-rose-400 font-bold">CRITICAL events</span> (such as Account Suspensions and Grace Period alerts) are always active and cannot be turned off to guarantee baseline service visibility.
            </div>
        </div>

        <form method="POST" action="{{ route('notifications.preferences.update') }}" class="space-y-6">
            @csrf

            <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden divide-y divide-slate-800/80">
                @foreach($categories as $key => $label)
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-white">{{ $label }}</h3>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    @if($key === 'billing')
                                        Subscription renewals, grace periods, plan upgrades, and overage pay-gates.
                                    @elseif($key === 'wallet')
                                        Top-ups, debits, low balance thresholds, and wallet lock notices.
                                    @else
                                        Monthly ROI reports, consumer counts, and data extraction summaries.
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                            <!-- In-App (Locked / Always On for Critical) -->
                            <div class="p-3.5 bg-slate-950/60 rounded-xl border border-slate-800 flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-semibold text-white flex items-center gap-1.5">
                                        <span>🖥️ In-App Center</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">Always active</div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    Enabled
                                </span>
                            </div>

                            <!-- Email Toggle -->
                            <div class="p-3.5 bg-slate-950/60 rounded-xl border border-slate-800 flex items-center justify-between">
                                <div>
                                    <label for="pref_{{ $key }}_email" class="text-xs font-semibold text-white flex items-center gap-1.5 cursor-pointer">
                                        <span>📧 Email Alerts</span>
                                    </label>
                                    <div class="text-[10px] text-slate-400 mt-0.5">Transactional inbox</div>
                                </div>
                                <input type="checkbox" name="preferences[{{ $key }}][email]" value="1" id="pref_{{ $key }}_email" {{ !empty($preferences[$key]['email']) ? 'checked' : '' }} class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer" />
                            </div>

                            <!-- Push Toggle -->
                            <div class="p-3.5 bg-slate-950/60 rounded-xl border border-slate-800 flex items-center justify-between">
                                <div>
                                    <label for="pref_{{ $key }}_push" class="text-xs font-semibold text-white flex items-center gap-1.5 cursor-pointer">
                                        <span>📲 Push / Browser</span>
                                    </label>
                                    <div class="text-[10px] text-slate-400 mt-0.5">Instant alerts</div>
                                </div>
                                <input type="checkbox" name="preferences[{{ $key }}][push]" value="1" id="pref_{{ $key }}_push" {{ !empty($preferences[$key]['push']) ? 'checked' : '' }} class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30">
                    Save Notification Preferences
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
