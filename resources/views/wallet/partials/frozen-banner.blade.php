@if($user->isWalletFrozen())
    <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/80 text-rose-900 dark:text-rose-200 text-xs flex items-start gap-3 shadow-sm">
        <span class="text-lg">🔒</span>
        <div class="space-y-0.5">
            <div class="font-bold">Wallet Frozen by Administrator</div>
            <p class="text-rose-700 dark:text-rose-300 text-[11px]">
                {{ $user->wallet_frozen_reason ?: 'Debits on this wallet are temporarily paused. Contact billing support if you need assistance.' }}
            </p>
        </div>
    </div>
@endif
