<!-- TRUE SLIDING CARD CAROUSEL VIEW -->
<div x-show="items.length > 0 && viewMode === 'card'"
     :class="loading ? 'opacity-40 pointer-events-none transition-opacity duration-150' : 'opacity-100 transition-opacity duration-150'"
     class="space-y-6">
    <!-- Slider Window / Track Container with Swipe Gestures -->
    <div class="overflow-hidden w-full max-w-lg mx-auto rounded-3xl touch-pan-y touch-pinch-zoom"
         @touchstart="if ($event.touches && $event.touches.length > 1) { isPinching = true; } else { isPinching = false; touchStartX = $event.changedTouches[0].screenX; touchStartY = $event.changedTouches[0].screenY; }"
         @touchend="handleTouchEnd($event)">
        
        <!-- Dynamic Sliding Track -->
        <div class="flex transition-transform duration-300 ease-out will-change-transform"
             :style="'transform: translateX(-' + (currentCardIndex * 100) + '%);'">
            
            <template x-for="(bill, index) in items" :key="bill.id">
                <div class="min-w-full w-full shrink-0 px-1 box-border">
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border shadow-xl overflow-hidden transition-all duration-200" :class="bill._syncError ? 'ring-2 ring-rose-500 border-rose-500 shadow-rose-500/20' : (colorSettings?.enabled ? getAvgUnitStyle(bill.smart_avg_units, 'border') : 'border-slate-200/90 dark:border-slate-800')">
                        @include('dashboard.partials.card.card-header')
                        @include('dashboard.partials.card.meta-banner')
                        @include('dashboard.partials.card.boxes-grid')
                        @include('dashboard.partials.card.action-buttons')
                        @include('dashboard.partials.card.remark-section')
                        @include('dashboard.partials.card.tag-section')
                        @include('dashboard.partials.card.card-footer')
                    </div>
                </div>
            </template>
        </div>
    </div>

    @include('dashboard.partials.card.carousel-navigation')
</div>
