<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Keyboard Shortcuts Configuration
    |--------------------------------------------------------------------------
    |
    | These default keybindings are assigned to all users by default.
    | Users can customize these in their profile/dashboard settings.
    |
    */
    'default' => [
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
    ],

    'labels' => [
        'copy_ca' => 'Copy CA Number to Clipboard',
        'focus_reading' => 'Edit / Focus Working Reading Input',
        'auto_fill_reading' => 'Auto-Fill Reading (Prev + Avg)',
        'submit_ok' => 'Save & Mark as Submit / OK',
        'mark_doubt' => 'Mark as Doubt / Re-check',
        'mark_critical' => 'Mark as Critical / Issue',
        'next_card' => 'Navigate to Next Consumer Card',
        'prev_card' => 'Navigate to Previous Consumer Card',
        'open_remark' => 'Open / Focus Remark Note Field',
        'exit_box' => 'Exit / Unfocus Input Box (Back to Navigation)',
    ],
];
