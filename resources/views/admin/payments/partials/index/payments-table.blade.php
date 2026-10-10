{{-- Payments Table --}}
<div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            @include('admin.payments.partials.index.table.thead')
            <tbody class="divide-y divide-slate-800/60">
                @forelse($payments as $payment)
                    @include('admin.payments.partials.index.table.row', ['payment' => $payment])
                @empty
                    @include('admin.payments.partials.index.table.empty-state')
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
        <div class="p-4 border-t border-slate-800">
            {{ $payments->links() }}
        </div>
    @endif
</div>
