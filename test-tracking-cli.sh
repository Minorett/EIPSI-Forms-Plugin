#!/bin/bash
# EIPSI Forms Tracking Handler Test Script
# This script tests the tracking implementation using WP-CLI

set -e  # Exit on error

echo "================================================"
echo "EIPSI Forms Tracking Handler Test Suite"
echo "================================================"
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
TESTS_PASSED=0
TESTS_FAILED=0

# Function to print test results
print_test_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ PASS${NC}: $2"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "${RED}✗ FAIL${NC}: $2"
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
}

# Check if WP-CLI is available
if ! command -v wp &> /dev/null; then
    echo -e "${RED}Error: WP-CLI is not installed or not in PATH${NC}"
    echo "Please install WP-CLI: https://wp-cli.org/"
    exit 1
fi

# Check if WordPress is accessible
wp core is-installed &> /dev/null
if [ $? -ne 0 ]; then
    echo -e "${RED}Error: WordPress installation not found or not properly configured${NC}"
    exit 1
fi

echo "WordPress found and accessible"
echo ""

# Test 1: Check if database table exists
echo "Test 1: Checking if vas_form_events table exists..."
TABLE_EXISTS=$(wp db query "SHOW TABLES LIKE '%vas_form_events%';" --skip-column-names 2>/dev/null | wc -l)
if [ "$TABLE_EXISTS" -gt 0 ]; then
    print_test_result 0 "Database table exists"
else
    print_test_result 1 "Database table does not exist"
    echo "  → Run plugin activation: wp plugin activate vas-dinamico-forms"
fi
echo ""

# Test 2: Check table structure
echo "Test 2: Verifying table structure..."
COLUMNS=$(wp db query "DESCRIBE wp_vas_form_events;" --skip-column-names 2>/dev/null | wc -l)
if [ "$COLUMNS" -eq 8 ]; then
    print_test_result 0 "Table has correct number of columns (8)"
else
    print_test_result 1 "Table structure incorrect (expected 8 columns, found $COLUMNS)"
fi
echo ""

# Test 3: Check if AJAX handler is registered
echo "Test 3: Checking if AJAX handler function exists..."
HANDLER_EXISTS=$(wp eval "echo function_exists('eipsi_track_event_handler') ? 'yes' : 'no';" 2>/dev/null)
if [ "$HANDLER_EXISTS" = "yes" ]; then
    print_test_result 0 "AJAX handler function exists"
else
    print_test_result 1 "AJAX handler function not found"
fi
echo ""

# Generate a unique session ID for testing
TEST_SESSION_ID="cli-test-$(date +%s)"

# Test 4: Test 'view' event tracking
echo "Test 4: Testing 'view' event tracking..."
wp eval "
\$_POST['nonce'] = wp_create_nonce('eipsi_tracking_nonce');
\$_POST['form_id'] = 'cli-test-form';
\$_POST['session_id'] = '$TEST_SESSION_ID';
\$_POST['event_type'] = 'view';
\$_POST['user_agent'] = 'WP-CLI Test Script';

ob_start();
try {
    do_action('wp_ajax_nopriv_eipsi_track_event');
    \$output = ob_get_clean();
    \$result = json_decode(\$output, true);
    echo \$result['success'] ? 'success' : 'failed';
} catch (Exception \$e) {
    ob_end_clean();
    echo 'error';
}
" 2>/dev/null > /tmp/test_result.txt

RESULT=$(cat /tmp/test_result.txt)
if [ "$RESULT" = "success" ]; then
    print_test_result 0 "'view' event tracked successfully"
else
    print_test_result 1 "'view' event tracking failed"
fi
echo ""

# Test 5: Test 'start' event tracking
echo "Test 5: Testing 'start' event tracking..."
wp eval "
\$_POST['nonce'] = wp_create_nonce('eipsi_tracking_nonce');
\$_POST['form_id'] = 'cli-test-form';
\$_POST['session_id'] = '$TEST_SESSION_ID';
\$_POST['event_type'] = 'start';
\$_POST['user_agent'] = 'WP-CLI Test Script';

ob_start();
do_action('wp_ajax_nopriv_eipsi_track_event');
\$output = ob_get_clean();
\$result = json_decode(\$output, true);
echo \$result['success'] ? 'success' : 'failed';
" 2>/dev/null > /tmp/test_result.txt

RESULT=$(cat /tmp/test_result.txt)
if [ "$RESULT" = "success" ]; then
    print_test_result 0 "'start' event tracked successfully"
else
    print_test_result 1 "'start' event tracking failed"
fi
echo ""

# Test 6: Test 'page_change' event with page_number
echo "Test 6: Testing 'page_change' event with page number..."
wp eval "
\$_POST['nonce'] = wp_create_nonce('eipsi_tracking_nonce');
\$_POST['form_id'] = 'cli-test-form';
\$_POST['session_id'] = '$TEST_SESSION_ID';
\$_POST['event_type'] = 'page_change';
\$_POST['page_number'] = '2';
\$_POST['user_agent'] = 'WP-CLI Test Script';

ob_start();
do_action('wp_ajax_nopriv_eipsi_track_event');
\$output = ob_get_clean();
\$result = json_decode(\$output, true);
echo \$result['success'] ? 'success' : 'failed';
" 2>/dev/null > /tmp/test_result.txt

RESULT=$(cat /tmp/test_result.txt)
if [ "$RESULT" = "success" ]; then
    print_test_result 0 "'page_change' event tracked successfully"
else
    print_test_result 1 "'page_change' event tracking failed"
fi
echo ""

# Test 7: Test 'submit' event tracking
echo "Test 7: Testing 'submit' event tracking..."
wp eval "
\$_POST['nonce'] = wp_create_nonce('eipsi_tracking_nonce');
\$_POST['form_id'] = 'cli-test-form';
\$_POST['session_id'] = '$TEST_SESSION_ID';
\$_POST['event_type'] = 'submit';
\$_POST['user_agent'] = 'WP-CLI Test Script';

ob_start();
do_action('wp_ajax_nopriv_eipsi_track_event');
\$output = ob_get_clean();
\$result = json_decode(\$output, true);
echo \$result['success'] ? 'success' : 'failed';
" 2>/dev/null > /tmp/test_result.txt

RESULT=$(cat /tmp/test_result.txt)
if [ "$RESULT" = "success" ]; then
    print_test_result 0 "'submit' event tracked successfully"
else
    print_test_result 1 "'submit' event tracking failed"
fi
echo ""

# Test 8: Test 'branch_jump' event with metadata
echo "Test 8: Testing 'branch_jump' event with metadata..."
wp eval "
\$_POST['nonce'] = wp_create_nonce('eipsi_tracking_nonce');
\$_POST['form_id'] = 'cli-test-form';
\$_POST['session_id'] = '$TEST_SESSION_ID';
\$_POST['event_type'] = 'branch_jump';
\$_POST['from_page'] = '2';
\$_POST['to_page'] = '5';
\$_POST['field_id'] = 'test-field-123';
\$_POST['matched_value'] = 'Option A';
\$_POST['user_agent'] = 'WP-CLI Test Script';

ob_start();
do_action('wp_ajax_nopriv_eipsi_track_event');
\$output = ob_get_clean();
\$result = json_decode(\$output, true);
echo \$result['success'] ? 'success' : 'failed';
" 2>/dev/null > /tmp/test_result.txt

RESULT=$(cat /tmp/test_result.txt)
if [ "$RESULT" = "success" ]; then
    print_test_result 0 "'branch_jump' event tracked successfully"
else
    print_test_result 1 "'branch_jump' event tracking failed"
fi
echo ""

# Test 9: Test invalid event type (should fail gracefully)
echo "Test 9: Testing invalid event type rejection..."
wp eval "
\$_POST['nonce'] = wp_create_nonce('eipsi_tracking_nonce');
\$_POST['form_id'] = 'cli-test-form';
\$_POST['session_id'] = '$TEST_SESSION_ID';
\$_POST['event_type'] = 'invalid_event';
\$_POST['user_agent'] = 'WP-CLI Test Script';

ob_start();
do_action('wp_ajax_nopriv_eipsi_track_event');
\$output = ob_get_clean();
\$result = json_decode(\$output, true);
echo \$result['success'] ? 'accepted' : 'rejected';
" 2>/dev/null > /tmp/test_result.txt

RESULT=$(cat /tmp/test_result.txt)
if [ "$RESULT" = "rejected" ]; then
    print_test_result 0 "Invalid event type correctly rejected"
else
    print_test_result 1 "Invalid event type was accepted (should be rejected)"
fi
echo ""

# Test 10: Test missing session_id (should fail gracefully)
echo "Test 10: Testing missing session_id rejection..."
wp eval "
\$_POST['nonce'] = wp_create_nonce('eipsi_tracking_nonce');
\$_POST['form_id'] = 'cli-test-form';
\$_POST['event_type'] = 'view';
\$_POST['user_agent'] = 'WP-CLI Test Script';
// Intentionally omit session_id

ob_start();
do_action('wp_ajax_nopriv_eipsi_track_event');
\$output = ob_get_clean();
\$result = json_decode(\$output, true);
echo \$result['success'] ? 'accepted' : 'rejected';
" 2>/dev/null > /tmp/test_result.txt

RESULT=$(cat /tmp/test_result.txt)
if [ "$RESULT" = "rejected" ]; then
    print_test_result 0 "Missing session_id correctly rejected"
else
    print_test_result 1 "Missing session_id was accepted (should be rejected)"
fi
echo ""

# Test 11: Verify database entries
echo "Test 11: Verifying database entries for test session..."
EVENT_COUNT=$(wp db query "SELECT COUNT(*) FROM wp_vas_form_events WHERE session_id = '$TEST_SESSION_ID';" --skip-column-names 2>/dev/null)
if [ "$EVENT_COUNT" -ge 5 ]; then
    print_test_result 0 "Database entries created ($EVENT_COUNT events found)"
else
    print_test_result 1 "Not enough database entries (expected >= 5, found $EVENT_COUNT)"
fi
echo ""

# Test 12: Verify branch_jump metadata stored correctly
echo "Test 12: Verifying branch_jump metadata storage..."
METADATA_CHECK=$(wp db query "SELECT metadata FROM wp_vas_form_events WHERE session_id = '$TEST_SESSION_ID' AND event_type = 'branch_jump' LIMIT 1;" --skip-column-names 2>/dev/null)
if [ ! -z "$METADATA_CHECK" ] && [ "$METADATA_CHECK" != "NULL" ]; then
    print_test_result 0 "Branch jump metadata stored correctly"
else
    print_test_result 1 "Branch jump metadata not stored"
fi
echo ""

# Display test session data
echo "================================================"
echo "Test Session Data ($TEST_SESSION_ID):"
echo "================================================"
wp db query "SELECT id, event_type, page_number, metadata, created_at FROM wp_vas_form_events WHERE session_id = '$TEST_SESSION_ID' ORDER BY created_at;" 2>/dev/null
echo ""

# Summary
echo "================================================"
echo "Test Summary"
echo "================================================"
echo -e "${GREEN}Passed: $TESTS_PASSED${NC}"
echo -e "${RED}Failed: $TESTS_FAILED${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
    echo ""
    echo "The EIPSI tracking handler is working correctly."
    exit 0
else
    echo -e "${RED}✗ Some tests failed${NC}"
    echo ""
    echo "Please review the failed tests above and check:"
    echo "  1. Plugin is activated"
    echo "  2. Database tables are created"
    echo "  3. AJAX handlers are properly registered"
    exit 1
fi

# Cleanup
rm -f /tmp/test_result.txt
