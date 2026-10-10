<!-- Consumer Mobile Number (Compact, Zero Size Impact, 1-Click Copy & Override) -->
<div class="inline-flex items-center gap-1">
    <template x-if="bill.mobile">
        <div class="inline-flex items-center rounded-md text-[10px] font-bold font-mono transition border select-none overflow-hidden"
             :class="copiedMobileId === bill.id ? 'bg-emerald-500/40 border-emerald-400 text-emerald-100' : 'bg-emerald-950/50 hover:bg-emerald-900/60 text-emerald-300 border-emerald-500/40'">
            <button type="button" 
                    @click.stop="copyMobile(bill)"
                    class="inline-flex items-center gap-1 px-1.5 py-0.5 hover:text-white transition active:scale-95 touch-manipulation cursor-pointer"
                    :title="'Tap to copy mobile: ' + bill.mobile">
                <span class="text-[9px]">📱</span>
                <span x-text="copiedMobileId === bill.id ? 'Copied!' : bill.mobile"></span>
            </button>
            <button type="button"
                    @click.stop="openMobileModal(bill)"
                    class="px-1 py-0.5 border-l border-emerald-500/30 hover:bg-emerald-500/20 text-emerald-200 hover:text-white transition cursor-pointer"
                    title="Edit / Override Mobile Number">
                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            </button>
        </div>
    </template>
    <template x-if="!bill.mobile">
        <button type="button"
                @click.stop="openMobileModal(bill)"
                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold font-mono transition border border-dashed border-white/20 text-slate-300 hover:text-white hover:bg-white/10 active:scale-95 select-none cursor-pointer"
                title="Add consumer mobile number">
            <span class="text-[9px]">📱</span>
            <span>+Mobile</span>
        </button>
    </template>
</div>

<!-- FieldDesk Bridge Badge (Module 11) - Zero Card Size Expansion -->
<div class="inline-flex items-center">
    <template x-if="bill.field_desk_action">
        <button type="button"
                @click.stop="openFieldDeskModal(bill)"
                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold font-mono transition border select-none active:scale-95 touch-manipulation cursor-pointer"
                :class="{
                    'bg-rose-500/40 border-rose-400 text-rose-100 animate-pulse': bill.field_desk_action.is_due_today,
                    'bg-amber-500/40 border-amber-400 text-amber-100': bill.field_desk_action.is_overdue,
                    'bg-indigo-500/40 border-indigo-400 text-indigo-100': bill.field_desk_action.is_upcoming,
                    'bg-emerald-500/40 border-emerald-400 text-emerald-100': bill.field_desk_action.status === 'completed'
                }"
                :title="'FieldDesk: ' + bill.field_desk_action.category_name + (bill.field_desk_action.target_date_formatted ? ' (' + bill.field_desk_action.target_date_formatted + ')' : '')">
            <span x-text="bill.field_desk_action.category_icon || '📋'"></span>
            <span x-text="bill.field_desk_action.is_due_today ? '🚨 Today' : (bill.field_desk_action.is_overdue ? '⚠️ Overdue' : (bill.field_desk_action.target_date_formatted || 'FieldDesk'))"></span>
            <template x-if="bill.field_desk_action.target_amount > 0">
                <span class="text-[9px] opacity-90">₹<span x-text="Math.round(bill.field_desk_action.target_amount)"></span></span>
            </template>
        </button>
    </template>
    <template x-if="!bill.field_desk_action">
        <button type="button"
                @click.stop="openFieldDeskModal(bill)"
                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold font-mono transition border border-dashed border-white/20 text-slate-300 hover:text-white hover:bg-white/10 active:scale-95 select-none cursor-pointer"
                title="Add FieldDesk Action / Follow-up [Alt+F]">
            <span class="text-[9px]">📋</span>
            <span>+Desk</span>
        </button>
    </template>
</div>

<!-- GPS Map Badge - Zero Card Size Expansion -->
<template x-if="bill.latitude && bill.longitude">
    <a :href="bill.map_link || ('https://www.google.com/maps?q=' + bill.latitude + ',' + bill.longitude)"
       target="_blank"
       @click.stop
       class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-bold font-mono transition bg-rose-500/30 border border-rose-400/70 text-rose-100 hover:bg-rose-500/50 hover:text-white select-none active:scale-95 cursor-pointer"
       :title="'Open GPS coordinates in Google Maps' + (bill.location_accuracy ? ' (±' + Math.round(bill.location_accuracy) + 'm)' : '')">
        <span class="text-[9px]">📍</span>
        <span>Map</span>
    </a>
</template>
