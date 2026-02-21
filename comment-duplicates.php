<?php
$file = 'admin/ajax-handlers.php';
$content = file_get_contents($file);

// Comment out the 4 function definitions (keep add_action hooks)
$replacements = [
    'function eipsi_participant_register_handler()' => '// DUPLICATE MOVED TO ajax-participant-handlers.php\n// function eipsi_participant_register_handler()',
    'function eipsi_participant_login_handler()' => '// DUPLICATE MOVED TO ajax-participant-handlers.php\n// function eipsi_participant_login_handler()',
    'function eipsi_participant_logout_handler()' => '// DUPLICATE MOVED TO ajax-participant-handlers.php\n// function eipsi_participant_logout_handler()',
    'function eipsi_participant_info_handler()' => '// DUPLICATE MOVED TO ajax-participant-handlers.php\n// function eipsi_participant_info_handler()',
];

foreach ($replacements as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

file_put_contents($file, $content);
echo "✓ Commented out duplicate functions\n";
