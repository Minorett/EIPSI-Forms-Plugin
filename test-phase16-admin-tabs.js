/**
 * Phase 16: Admin Panel Consolidation - Validation Tests
 * 
 * Validates:
 * - File structure
 * - Tab files exist and are properly structured
 * - Menu registration updated
 * - Main results-page.php refactored correctly
 * - Tab navigation logic
 * - Security patterns
 */

const fs = require('fs');
const path = require('path');

let passed = 0;
let failed = 0;

function test(description, fn) {
    try {
        fn();
        console.log(`✅ ${description}`);
        passed++;
    } catch (error) {
        console.error(`❌ ${description}`);
        console.error(`   ${error.message}`);
        failed++;
    }
}

function assert(condition, message) {
    if (!condition) {
        throw new Error(message);
    }
}

function fileExists(filePath) {
    return fs.existsSync(filePath);
}

function fileContains(filePath, searchString) {
    const content = fs.readFileSync(filePath, 'utf8');
    return content.includes(searchString);
}

function fileContainsPattern(filePath, pattern) {
    const content = fs.readFileSync(filePath, 'utf8');
    return pattern.test(content);
}

console.log('\n🧪 Phase 16: Admin Panel Consolidation - Validation Tests\n');

// ============================================================================
// 1. FILE STRUCTURE TESTS
// ============================================================================

console.log('📂 File Structure Tests\n');

test('admin/tabs/ directory exists', () => {
    assert(fs.existsSync('admin/tabs'), 'admin/tabs directory should exist');
    assert(fs.statSync('admin/tabs').isDirectory(), 'admin/tabs should be a directory');
});

test('admin/tabs/submissions-tab.php exists', () => {
    assert(fileExists('admin/tabs/submissions-tab.php'), 'submissions-tab.php should exist');
});

test('admin/tabs/completion-message-tab.php exists', () => {
    assert(fileExists('admin/tabs/completion-message-tab.php'), 'completion-message-tab.php should exist');
});

test('admin/tabs/privacy-metadata-tab.php exists', () => {
    assert(fileExists('admin/tabs/privacy-metadata-tab.php'), 'privacy-metadata-tab.php should exist');
});

test('admin/results-page.php exists (refactored)', () => {
    assert(fileExists('admin/results-page.php'), 'results-page.php should exist');
});

test('admin/menu.php exists', () => {
    assert(fileExists('admin/menu.php'), 'menu.php should exist');
});

// ============================================================================
// 2. MENU REGISTRATION TESTS
// ============================================================================

console.log('\n📋 Menu Registration Tests\n');

test('Menu item renamed to "Results & Experience"', () => {
    assert(
        fileContains('admin/menu.php', '__("Results & Experience"'),
        'Menu should display "Results & Experience"'
    );
});

test('Menu slug unchanged (vas-dinamico-results)', () => {
    assert(
        fileContains('admin/menu.php', "'vas-dinamico-results'"),
        'Menu slug should remain vas-dinamico-results'
    );
});

test('Menu callback function unchanged (vas_display_form_responses)', () => {
    assert(
        fileContains('admin/menu.php', 'vas_display_form_responses'),
        'Callback function should remain vas_display_form_responses'
    );
});

// ============================================================================
// 3. MAIN PAGE REFACTOR TESTS
// ============================================================================

console.log('\n🔄 Main Page Refactor Tests\n');

test('results-page.php contains tab navigation', () => {
    assert(
        fileContains('admin/results-page.php', 'nav-tab-wrapper'),
        'Should have WordPress nav-tab-wrapper'
    );
});

test('results-page.php handles tab parameter', () => {
    assert(
        fileContains('admin/results-page.php', '$active_tab = isset($_GET[\'tab\'])'),
        'Should handle ?tab parameter'
    );
});

test('results-page.php has allowed_tabs array', () => {
    assert(
        fileContains('admin/results-page.php', '$allowed_tabs = array('),
        'Should define allowed tabs'
    );
    assert(
        fileContains('admin/results-page.php', '\'submissions\''),
        'Should include submissions tab'
    );
    assert(
        fileContains('admin/results-page.php', '\'completion\''),
        'Should include completion tab'
    );
    assert(
        fileContains('admin/results-page.php', '\'privacy\''),
        'Should include privacy tab'
    );
});

test('results-page.php defaults to submissions tab', () => {
    assert(
        fileContains('admin/results-page.php', '$active_tab = \'submissions\''),
        'Should default to submissions tab'
    );
});

test('results-page.php includes submissions-tab.php', () => {
    assert(
        fileContains('admin/results-page.php', 'submissions-tab.php'),
        'Should include submissions-tab.php'
    );
});

test('results-page.php includes completion-message-tab.php', () => {
    assert(
        fileContains('admin/results-page.php', 'completion-message-tab.php'),
        'Should include completion-message-tab.php'
    );
});

test('results-page.php includes privacy-metadata-tab.php', () => {
    assert(
        fileContains('admin/results-page.php', 'privacy-metadata-tab.php'),
        'Should include privacy-metadata-tab.php'
    );
});

test('results-page.php has capability check', () => {
    assert(
        fileContains('admin/results-page.php', 'current_user_can(\'manage_options\')'),
        'Should verify user capability'
    );
});

test('results-page.php has tab styling', () => {
    assert(
        fileContains('admin/results-page.php', '.nav-tab-active'),
        'Should have CSS for active tab'
    );
    assert(
        fileContains('admin/results-page.php', 'border-bottom-color: #005a87'),
        'Should use EIPSI blue color'
    );
});

// ============================================================================
// 4. SUBMISSIONS TAB TESTS
// ============================================================================

console.log('\n📊 Submissions Tab Tests\n');

test('submissions-tab.php has security check', () => {
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'if (!defined(\'ABSPATH\'))'),
        'Should have ABSPATH check'
    );
});

test('submissions-tab.php accesses global $wpdb', () => {
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'global $wpdb'),
        'Should declare global $wpdb'
    );
});

test('submissions-tab.php has form filter', () => {
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'form_filter'),
        'Should have form filter dropdown'
    );
});

test('submissions-tab.php has export buttons', () => {
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'Download CSV'),
        'Should have CSV export button'
    );
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'Download Excel'),
        'Should have Excel export button'
    );
});

test('submissions-tab.php has submissions table', () => {
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'wp-list-table widefat'),
        'Should have WordPress table styling'
    );
});

test('submissions-tab.php has view/delete actions', () => {
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'vas-view-response'),
        'Should have view action'
    );
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'vas-delete-response'),
        'Should have delete action'
    );
});

test('submissions-tab.php has AJAX modal', () => {
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'vas-response-modal'),
        'Should have response modal'
    );
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'jQuery(document).ready'),
        'Should have jQuery initialization'
    );
});

test('submissions-tab.php preserves tab parameter', () => {
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'tab=submissions'),
        'Should preserve tab in URLs'
    );
});

// ============================================================================
// 5. COMPLETION MESSAGE TAB TESTS
// ============================================================================

console.log('\n✅ Completion Message Tab Tests\n');

test('completion-message-tab.php has security check', () => {
    assert(
        fileContains('admin/tabs/completion-message-tab.php', 'if (!defined(\'ABSPATH\'))'),
        'Should have ABSPATH check'
    );
});

test('completion-message-tab.php requires backend class', () => {
    assert(
        fileContains('admin/tabs/completion-message-tab.php', 'completion-message-backend.php'),
        'Should require backend class'
    );
});

test('completion-message-tab.php gets config', () => {
    assert(
        fileContains('admin/tabs/completion-message-tab.php', 'EIPSI_Completion_Message::get_config()'),
        'Should get completion message config'
    );
});

test('completion-message-tab.php has wp_editor', () => {
    assert(
        fileContains('admin/tabs/completion-message-tab.php', 'wp_editor('),
        'Should have wp_editor for message'
    );
});

test('completion-message-tab.php has form options', () => {
    assert(
        fileContains('admin/tabs/completion-message-tab.php', 'show_logo'),
        'Should have logo option'
    );
    assert(
        fileContains('admin/tabs/completion-message-tab.php', 'show_home_button'),
        'Should have home button option'
    );
    assert(
        fileContains('admin/tabs/completion-message-tab.php', 'redirect_url'),
        'Should have redirect URL option'
    );
});

test('completion-message-tab.php has AJAX save', () => {
    assert(
        fileContains('admin/tabs/completion-message-tab.php', 'eipsi_save_completion_message'),
        'Should call save AJAX action'
    );
    assert(
        fileContains('admin/tabs/completion-message-tab.php', 'addEventListener(\'submit\''),
        'Should have form submit handler'
    );
});

test('completion-message-tab.php has nonce', () => {
    assert(
        fileContains('admin/tabs/completion-message-tab.php', 'wp_nonce_field(\'eipsi_admin_nonce\''),
        'Should have nonce field'
    );
});

test('completion-message-tab.php has preview iframe', () => {
    assert(
        fileContains('admin/tabs/completion-message-tab.php', '<iframe'),
        'Should have preview iframe'
    );
    assert(
        fileContains('admin/tabs/completion-message-tab.php', 'EIPSI_Completion_Message::get_page_url()'),
        'Should use completion page URL'
    );
});

// ============================================================================
// 6. PRIVACY METADATA TAB TESTS
// ============================================================================

console.log('\n🔒 Privacy & Metadata Tab Tests\n');

test('privacy-metadata-tab.php has security check', () => {
    assert(
        fileContains('admin/tabs/privacy-metadata-tab.php', 'if (!defined(\'ABSPATH\'))'),
        'Should have ABSPATH check'
    );
});

test('privacy-metadata-tab.php includes privacy-dashboard.php', () => {
    assert(
        fileContains('admin/tabs/privacy-metadata-tab.php', 'privacy-dashboard.php'),
        'Should include privacy-dashboard.php'
    );
});

test('privacy-metadata-tab.php calls render function', () => {
    assert(
        fileContains('admin/tabs/privacy-metadata-tab.php', 'render_privacy_dashboard()'),
        'Should call render_privacy_dashboard()'
    );
});

// ============================================================================
// 7. SECURITY PATTERN TESTS
// ============================================================================

console.log('\n🔐 Security Pattern Tests\n');

test('All tab files have ABSPATH check', () => {
    const tabFiles = [
        'admin/tabs/submissions-tab.php',
        'admin/tabs/completion-message-tab.php',
        'admin/tabs/privacy-metadata-tab.php'
    ];
    
    tabFiles.forEach(file => {
        assert(
            fileContains(file, 'if (!defined(\'ABSPATH\'))'),
            `${file} should have ABSPATH check`
        );
    });
});

test('results-page.php uses sanitize_key for tab', () => {
    assert(
        fileContains('admin/results-page.php', 'sanitize_key($_GET[\'tab\'])'),
        'Should sanitize tab parameter'
    );
});

test('results-page.php validates allowed tabs', () => {
    assert(
        fileContains('admin/results-page.php', 'in_array($active_tab, $allowed_tabs)'),
        'Should validate tab against whitelist'
    );
});

test('submissions-tab.php uses sanitize_text_field', () => {
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'sanitize_text_field'),
        'Should sanitize user input'
    );
});

test('submissions-tab.php uses esc_html for output', () => {
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'esc_html'),
        'Should escape HTML output'
    );
});

test('submissions-tab.php uses esc_url for URLs', () => {
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'esc_url'),
        'Should escape URL output'
    );
});

test('submissions-tab.php uses wp_nonce_url for delete', () => {
    assert(
        fileContains('admin/tabs/submissions-tab.php', 'wp_nonce_url'),
        'Should use nonce for delete action'
    );
});

// ============================================================================
// 8. TRANSLATION TESTS
// ============================================================================

console.log('\n🌐 Translation Tests\n');

test('Tab names are translatable', () => {
    assert(
        fileContains('admin/results-page.php', '__("Submissions"') ||
        fileContains('admin/results-page.php', '_e("Submissions"'),
        'Submissions should be translatable'
    );
    assert(
        fileContains('admin/results-page.php', '__("Completion Message"') ||
        fileContains('admin/results-page.php', '_e("Completion Message"'),
        'Completion Message should be translatable'
    );
    assert(
        fileContains('admin/results-page.php', '__("Privacy & Metadata"') ||
        fileContains('admin/results-page.php', '_e("Privacy & Metadata"'),
        'Privacy & Metadata should be translatable'
    );
});

test('Tab content uses translation functions', () => {
    const tabFiles = [
        'admin/tabs/submissions-tab.php',
        'admin/tabs/completion-message-tab.php'
    ];
    
    tabFiles.forEach(file => {
        assert(
            fileContains(file, '__("') || fileContains(file, '_e("'),
            `${file} should use translation functions`
        );
    });
});

// ============================================================================
// 9. DOCUMENTATION TESTS
// ============================================================================

console.log('\n📚 Documentation Tests\n');

test('PHASE16_ADMIN_CONSOLIDATION_SUMMARY.md exists', () => {
    assert(fileExists('PHASE16_ADMIN_CONSOLIDATION_SUMMARY.md'), 'Summary documentation should exist');
});

test('QUICK_START_ADMIN_TABS.md exists', () => {
    assert(fileExists('QUICK_START_ADMIN_TABS.md'), 'Quick start guide should exist');
});

test('PHASE16_COMMIT_MESSAGE.txt exists', () => {
    assert(fileExists('PHASE16_COMMIT_MESSAGE.txt'), 'Commit message should exist');
});

test('README.md updated with admin structure', () => {
    assert(
        fileContains('README.md', 'Results & Experience'),
        'README should mention new admin panel name'
    );
    assert(
        fileContains('README.md', 'Tab 1: Submissions'),
        'README should document tab structure'
    );
});

// ============================================================================
// 10. BACKWARD COMPATIBILITY TESTS
// ============================================================================

console.log('\n🔄 Backward Compatibility Tests\n');

test('privacy-dashboard.php still exists', () => {
    assert(fileExists('admin/privacy-dashboard.php'), 'privacy-dashboard.php should still exist');
});

test('configuration.php unchanged', () => {
    assert(fileExists('admin/configuration.php'), 'configuration.php should exist');
});

test('ajax-handlers.php has completion message handler', () => {
    assert(
        fileContains('admin/ajax-handlers.php', 'eipsi_save_completion_message_handler'),
        'AJAX handler should exist'
    );
    assert(
        fileContains('admin/ajax-handlers.php', 'add_action(\'wp_ajax_eipsi_save_completion_message\''),
        'AJAX handler should be registered'
    );
});

test('completion-message-backend.php exists', () => {
    assert(fileExists('admin/completion-message-backend.php'), 'Backend class should exist');
});

// ============================================================================
// SUMMARY
// ============================================================================

console.log('\n' + '='.repeat(70));
console.log('📊 TEST SUMMARY');
console.log('='.repeat(70));
console.log(`✅ Passed: ${passed}`);
console.log(`❌ Failed: ${failed}`);
console.log(`📈 Total:  ${passed + failed}`);
console.log(`🎯 Success Rate: ${((passed / (passed + failed)) * 100).toFixed(1)}%`);
console.log('='.repeat(70));

if (failed === 0) {
    console.log('\n🎉 All tests passed! Phase 16 implementation is complete and verified.\n');
    process.exit(0);
} else {
    console.log(`\n⚠️  ${failed} test(s) failed. Please review the errors above.\n`);
    process.exit(1);
}
