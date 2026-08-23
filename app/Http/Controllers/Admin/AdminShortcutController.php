<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminShortcutController extends Controller
{
    /**
     * Display the system-wide shortcut configuration panel.
     */
    public function index(): View
    {
        $baseDefaults = config('shortcuts.default', [
            'copy_ca' => 'c',
            'focus_reading' => 'r',
            'auto_fill_reading' => 'a',
            'submit_ok' => 'Enter',
            'mark_doubt' => '2',
            'mark_critical' => '3',
            'next_card' => 'ArrowDown',
            'prev_card' => 'ArrowUp',
            'open_remark' => 'm',
            'exit_box' => 'Escape',
        ]);

        $systemShortcuts = SystemSetting::get('shortcuts_default', $baseDefaults);
        $labels = config('shortcuts.labels', []);

        $stats = [
            'total_users' => User::count(),
            'customized_users' => User::whereNotNull('shortcuts')->count(),
            'default_users' => User::whereNull('shortcuts')->count(),
            'is_customized_from_factory' => ($systemShortcuts !== $baseDefaults),
        ];

        return view('admin.shortcuts.index', compact('systemShortcuts', 'labels', 'baseDefaults', 'stats'));
    }

    /**
     * Update the system-wide default shortcut keybindings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shortcuts' => 'required|array',
            'shortcuts.copy_ca' => 'required|string|max:30',
            'shortcuts.focus_reading' => 'required|string|max:30',
            'shortcuts.auto_fill_reading' => 'required|string|max:30',
            'shortcuts.submit_ok' => 'required|string|max:30',
            'shortcuts.mark_doubt' => 'required|string|max:30',
            'shortcuts.mark_critical' => 'required|string|max:30',
            'shortcuts.next_card' => 'required|string|max:30',
            'shortcuts.prev_card' => 'required|string|max:30',
            'shortcuts.open_remark' => 'required|string|max:30',
            'shortcuts.exit_box' => 'nullable|string|max:30',
        ]);

        SystemSetting::set('shortcuts_default', $validated['shortcuts']);

        return redirect()->route('admin.shortcuts.index')
            ->with('success', 'System-wide default keyboard shortcuts updated successfully! All users using defaults will inherit these changes.');
    }

    /**
     * Reset system-wide defaults to original factory config.
     */
    public function resetToFactory(): RedirectResponse
    {
        $factoryDefaults = config('shortcuts.default', [
            'copy_ca' => 'c',
            'focus_reading' => 'r',
            'auto_fill_reading' => 'a',
            'submit_ok' => 'Enter',
            'mark_doubt' => '2',
            'mark_critical' => '3',
            'next_card' => 'ArrowDown',
            'prev_card' => 'ArrowUp',
            'open_remark' => 'm',
            'exit_box' => 'Escape',
        ]);

        SystemSetting::set('shortcuts_default', $factoryDefaults);

        return redirect()->route('admin.shortcuts.index')
            ->with('success', 'System default shortcuts restored to original factory defaults.');
    }

    /**
     * Reset all billing agent/operator custom shortcuts so everyone inherits system defaults.
     */
    public function resetAllUsers(): RedirectResponse
    {
        $count = User::whereNotNull('shortcuts')->count();
        User::whereNotNull('shortcuts')->update(['shortcuts' => null]);

        return redirect()->route('admin.shortcuts.index')
            ->with('success', "Successfully reset custom overrides for {$count} user(s). All users now use system default shortcuts.");
    }
}
