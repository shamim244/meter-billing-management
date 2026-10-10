<!-- 5. Smart Auto-Fill & Automation -->
<div class="space-y-3 border-b border-slate-100 dark:border-slate-800 pb-6">
    <label class="text-sm font-bold text-slate-900 dark:text-white block">Automation & Remark Preferences</label>

    <div class="space-y-2.5">
        <label class="flex items-start gap-3 p-4 rounded-2xl bg-slate-50/50 dark:bg-slate-950/50 border border-slate-200/80 dark:border-slate-800 cursor-pointer">
            <input type="checkbox" name="auto_fill_suggestion" value="1" class="mt-0.5 rounded text-brand-600 focus:ring-brand-500" {{ ($preferences['auto_fill_suggestion'] ?? true) ? 'checked' : '' }}>
            <div>
                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Auto-Suggest Projected Working Readings</div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Automatically suggest `Previous Reading + Avg kWh` projection for unreviewed bills while strictly enforcing `Reading >= PDF Reading`.</div>
            </div>
        </label>

        <label class="flex items-start gap-3 p-4 rounded-2xl bg-slate-50/50 dark:bg-slate-950/50 border border-slate-200/80 dark:border-slate-800 cursor-pointer">
            <input type="checkbox" name="sound_feedback" value="1" class="mt-0.5 rounded text-brand-600 focus:ring-brand-500" {{ ($preferences['sound_feedback'] ?? true) ? 'checked' : '' }}>
            <div>
                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Audio & Toast Notifications on Rapid Actions</div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Display floating toast notifications with 1-click Undo whenever an audit status is marked or changed.</div>
            </div>
        </label>

        <label class="flex items-start gap-3 p-4 rounded-2xl bg-slate-50/50 dark:bg-slate-950/50 border border-slate-200/80 dark:border-slate-800 cursor-pointer">
            <input type="checkbox" name="show_remark_presets" value="1" class="mt-0.5 rounded text-brand-600 focus:ring-brand-500" {{ ($preferences['show_remark_presets'] ?? false) ? 'checked' : '' }}>
            <div>
                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Show Quick Preset Pills under Remark Box</div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Display quick-insert button pills (e.g. "Door Locked", "Meter Burnt") below the Remark box. Default is hidden for a clean, minimal card.</div>
            </div>
        </label>
    </div>
</div>
