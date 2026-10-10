<!-- Pagination -->
@if($coupons->hasPages())
    <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/60">
        {{ $coupons->links() }}
    </div>
@endif
