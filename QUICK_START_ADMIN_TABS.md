# Quick Start: Admin Tabs System

## 🎯 Overview

The **Results & Experience** admin panel uses a tab-based architecture with 3 tabs:
1. **Submissions** - View and export form responses
2. **Completion Message** - Configure global thank-you page
3. **Privacy & Metadata** - Control metadata capture per form

---

## 📂 File Structure

```
admin/
├── results-page.php               ← Main tab container
├── tabs/
│   ├── submissions-tab.php        ← Tab 1: Submissions table
│   ├── completion-message-tab.php ← Tab 2: Message editor
│   └── privacy-metadata-tab.php   ← Tab 3: Privacy toggles
├── completion-message-backend.php ← Backend class
├── privacy-dashboard.php          ← Privacy rendering
└── ajax-handlers.php              ← AJAX endpoints
```

---

## 🔗 URL Structure

### Access Tabs
```
/wp-admin/admin.php?page=vas-dinamico-results&tab=submissions
/wp-admin/admin.php?page=vas-dinamico-results&tab=completion
/wp-admin/admin.php?page=vas-dinamico-results&tab=privacy
```

### Default Tab
- If no `?tab` parameter: defaults to `submissions`
- If invalid tab: defaults to `submissions`

---

## 🛠️ Adding a New Tab

### Step 1: Create Tab File
Create `admin/tabs/my-new-tab.php`:

```php
<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="eipsi-my-new-tab">
    <h2><?php _e('My New Tab', 'vas-dinamico-forms'); ?></h2>
    <!-- Tab content here -->
</div>
```

### Step 2: Register Tab in results-page.php

**Update allowed tabs:**
```php
$allowed_tabs = array('submissions', 'completion', 'privacy', 'my-new-tab');
```

**Add tab navigation link:**
```php
<a href="?page=vas-dinamico-results&tab=my-new-tab" 
   class="nav-tab <?php echo ($active_tab === 'my-new-tab') ? 'nav-tab-active' : ''; ?>">
    🎨 <?php _e('My New Tab', 'vas-dinamico-forms'); ?>
</a>
```

**Add tab content:**
```php
<?php if ($active_tab === 'my-new-tab'): ?>
    <div class="tab-content" data-tab="my-new-tab">
        <?php include dirname(__FILE__) . '/tabs/my-new-tab.php'; ?>
    </div>
<?php endif; ?>
```

---

## 💾 AJAX Save Pattern (Completion Message Tab)

### Frontend (JavaScript)
```javascript
document.getElementById('my-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'my_ajax_action');
    formData.set('nonce', formData.get('eipsi_nonce'));
    
    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Success feedback
        } else {
            // Error feedback
        }
    });
});
```

### Backend (PHP)
```php
function my_ajax_handler() {
    check_ajax_referer('eipsi_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }
    
    // Process data
    $result = my_save_function($data);
    
    if ($result) {
        wp_send_json_success(array('message' => 'Saved successfully'));
    } else {
        wp_send_json_error(array('message' => 'Save failed'));
    }
}
add_action('wp_ajax_my_ajax_action', 'my_ajax_handler');
```

---

## 🎨 Styling Guidelines

### Use WordPress Native Classes
```php
<h2 class="nav-tab-wrapper">
    <a href="..." class="nav-tab nav-tab-active">Tab Name</a>
</h2>
```

### Custom Styles (in tab container)
```css
.eipsi-results-page {
    background: white;
    padding: 20px;
    border-radius: 8px;
}

.nav-tab {
    color: #666;
    transition: all 0.3s ease;
}

.nav-tab-active {
    color: #005a87;
    border-bottom-color: #005a87;
    font-weight: 600;
}
```

### Color Variables
```css
--primary-clinical: #005a87;   /* EIPSI Blue */
--primary-hover: #003d5b;      /* Darker blue */
--background: #f8f9fa;         /* Clean gray */
--success: #28a745;            /* Green */
--error: #dc3545;              /* Red */
```

---

## 🔐 Security Checklist

### Always Include
- [x] `if (!defined('ABSPATH')) exit;` at top of file
- [x] `current_user_can('manage_options')` for admin pages
- [x] `wp_nonce_field()` in forms
- [x] `check_ajax_referer()` in AJAX handlers
- [x] `sanitize_*()` for input
- [x] `esc_*()` for output
- [x] `$wpdb->prepare()` for SQL

### Example
```php
<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('Unauthorized', 'vas-dinamico-forms'));
}

// Form
wp_nonce_field('eipsi_admin_nonce', 'eipsi_nonce');

// Input
$input = sanitize_text_field($_POST['field']);

// Output
echo esc_html($value);
echo esc_attr($attribute);
echo esc_url($url);
```

---

## 📊 Tab Navigation State Management

### Preserving Tab on Actions

**Delete action (submissions tab):**
```php
$url = add_query_arg([
    'action' => 'delete',
    'id' => $row->id,
    'tab' => 'submissions'  // ← Preserve tab
]);
```

**Form filter (submissions tab):**
```php
<form method="get">
    <input type="hidden" name="page" value="vas-dinamico-results">
    <input type="hidden" name="tab" value="submissions">  <!-- ← Preserve tab -->
    <select name="form_filter">...</select>
</form>
```

**Export links (submissions tab):**
```php
$export_url = add_query_arg([
    'action' => 'export_csv',
    'tab' => 'submissions'  // ← Preserve tab
]);
```

---

## 🧪 Testing Your Tab

### Manual Tests
1. Navigate to tab via URL
2. Verify active tab highlights
3. Refresh page - tab should persist
4. Test all forms/buttons
5. Check AJAX save feedback
6. Verify security (logout, try to access)

### Browser Console
```javascript
// Check for errors
console.log('No errors');

// Verify AJAX response
fetch(ajaxurl, {
    method: 'POST',
    body: new URLSearchParams({
        action: 'my_ajax_action',
        nonce: eipsi_admin_nonce
    })
})
.then(res => res.json())
.then(console.log);
```

### PHP Debugging
```php
// Enable debug mode
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Log data
error_log('My debug message: ' . print_r($data, true));
```

---

## 📱 Responsive Design

### Mobile Considerations
```css
@media (max-width: 768px) {
    .nav-tab {
        display: block;
        width: 100%;
    }
    
    .eipsi-submissions-tab table {
        overflow-x: auto;
    }
}
```

### Test Viewports
- Desktop: 1920px
- Laptop: 1366px
- Tablet: 768px
- Mobile: 375px

---

## 🚀 Deployment Checklist

### Before Merging
- [ ] All tabs load without errors
- [ ] AJAX saves work
- [ ] Forms submit correctly
- [ ] Nonces verified
- [ ] Input sanitized
- [ ] Output escaped
- [ ] Build successful (`npm run build`)
- [ ] No console errors
- [ ] Mobile responsive
- [ ] Tab state persists

### After Merging
- [ ] Test in production
- [ ] Verify all links work
- [ ] Check user permissions
- [ ] Monitor error logs
- [ ] Collect user feedback

---

## 📚 Related Files

- `admin/menu.php` - Menu registration
- `admin/ajax-handlers.php` - AJAX endpoints
- `admin/completion-message-backend.php` - Completion message class
- `admin/privacy-dashboard.php` - Privacy rendering
- `admin/export.php` - CSV/Excel export handlers

---

## 💡 Pro Tips

### 1. Use WordPress Functions
```php
// Good
wp_nonce_field('action', 'nonce');
check_ajax_referer('action', 'nonce');
current_user_can('manage_options');

// Avoid
$_SESSION['nonce'] = ...;  // Don't use sessions
if ($_POST['user'] === 'admin') { }  // Don't trust user input
```

### 2. Consistent Naming
```php
// Action names
'eipsi_save_completion_message'
'eipsi_delete_response'
'eipsi_export_csv'

// Nonce names
'eipsi_admin_nonce'
'delete_response_{id}'

// CSS classes
'eipsi-results-page'
'eipsi-completion-tab'
'eipsi-submissions-toolbar'
```

### 3. Translation Ready
```php
// Always use translation functions
__('Text', 'vas-dinamico-forms');
_e('Text', 'vas-dinamico-forms');
esc_html__('Text', 'vas-dinamico-forms');
```

### 4. Error Handling
```php
// AJAX responses
wp_send_json_success(array('message' => 'Success'));
wp_send_json_error(array('message' => 'Error'), 400);

// Page errors
wp_die(__('Unauthorized', 'vas-dinamico-forms'));
```

---

## 🎓 Learning Resources

- [WordPress Admin Menus](https://developer.wordpress.org/plugins/administration-menus/)
- [AJAX in Plugins](https://codex.wordpress.org/AJAX_in_Plugins)
- [Data Validation](https://developer.wordpress.org/plugins/security/data-validation/)
- [Nonces](https://developer.wordpress.org/plugins/security/nonces/)

---

**Quick Reference Complete** ✅

For full implementation details, see `PHASE16_ADMIN_CONSOLIDATION_SUMMARY.md`
