<?php
$file = 'admin/ajax-handlers.php';
$lines = file($file);

$functions_to_remove = [
    'eipsi_participant_register_handler',
    'eipsi_participant_login_handler',
    'eipsi_participant_logout_handler',
    'eipsi_participant_info_handler'
];

$in_function = false;
$brace_count = 0;
$current_function = '';
$start_line = -1;
$lines_to_remove = [];

foreach ($lines as $i => $line) {
    if (!$in_function) {
        foreach ($functions_to_remove as $func) {
            if (preg_match('/^function ' . preg_quote($func, '/') . '\(\)/', $line)) {
                $in_function = true;
                $brace_count = 1;
                $current_function = $func;
                $start_line = $i;
                $lines_to_remove[] = $i;
                break;
            }
        }
    } else {
        $lines_to_remove[] = $i;
        $brace_count += substr_count($line, '{') - substr_count($line, '}');
        if ($brace_count <= 0) {
            // Function ended
            $in_function = false;
            echo "✓ Function $current_function removed (lines $start_line-$i)\n";
        }
    }
}

// Remove lines (in reverse order to avoid shifting)
$lines_to_remove = array_unique($lines_to_remove);
rsort($lines_to_remove);
foreach ($lines_to_remove as $line_num) {
    unset($lines[$line_num]);
}

// Write back
file_put_contents($file, implode('', $lines));
echo "✓ Total lines removed: " . count($lines_to_remove) . "\n";
