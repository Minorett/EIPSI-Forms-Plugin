#!/bin/bash
# Fix duplicate function redeclaration error
# Removes duplicate participant authentication handlers from ajax-handlers.php

echo "Starting fix for duplicate function redeclaration..."

# Backup original file
cp admin/ajax-handlers.php admin/ajax-handlers.php.backup-before-fix-$(date +%Y%m%d-%H%M%S)
echo "✓ Backup created"

# Remove lines 2958-3137 (the 4 duplicate handlers)
sed -i '2958,3137d' admin/ajax-handlers.php
echo "✓ Removed duplicate handlers (lines 2958-3137)"

# Add comment explaining the removal
sed -i '2957a\
\
// =============================================================================\
// PARTICIPANT AUTHENTICATION AJAX HANDLERS\
// =============================================================================\
// NOTE: These handlers have been moved to ajax-participant-handlers.php (v1.5.5+)\
// The add_action hooks and function implementations are now in that file\
// to avoid duplication and fatal errors due to function redeclaration.\
//\
// The following functions are now defined in:\
// - admin/ajax-participant-handlers.php:\
//   * eipsi_participant_register_handler()\
//   * eipsi_participant_login_handler()\
//   * eipsi_participant_logout_handler()\
//   * eipsi_participant_info_handler()\
//\
// Rate limiting helper functions (kept here for potential future use):\
// * eipsi_check_login_rate_limit()\
// * eipsi_record_failed_login()\
// * eipsi_clear_login_rate_limit()\
// =============================================================================
' admin/ajax-handlers.php
echo "✓ Added documentation comment"

# Verify syntax
php -l admin/ajax-handlers.php
if [ $? -eq 0 ]; then
    echo "✓ PHP syntax is valid"
else
    echo "✗ PHP syntax error - please check the file"
    exit 1
fi

# Verify functions are removed
count=$(grep -c "^function eipsi_participant_.*_handler" admin/ajax-handlers.php)
if [ $count -eq 0 ]; then
    echo "✓ Duplicate functions successfully removed"
else
    echo "⚠ Warning: Found $count duplicate functions remaining"
fi

echo ""
echo "Fix completed successfully!"
echo "Please test:"
echo "  1. Plugin activation"
echo "  2. Participant registration"
echo "  3. Participant login/logout"
echo "  4. Participant info endpoint"
