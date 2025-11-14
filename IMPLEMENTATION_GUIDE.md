# Implementation Guide - Dual Database Writes

## Overview
This guide explains how the dual-database write system works in EIPSI Forms and how to maintain or extend it.

## Architecture

### Database Strategy
```
User Submits Form
       ↓
[WordPress Database] ← ALWAYS SAVE HERE FIRST (Primary Storage)
       ↓ (If successful)
       ├── Success? → YES → Continue
       └── Success? → NO → Return Error, Stop
       ↓
[Check External DB Enabled?]
       ↓
       ├── YES → Try External DB Insert
       │         ├── Success → Log success, continue
       │         └── Failure → Log error, continue anyway
       └── NO → Skip external DB
       ↓
[Return Success Response]
```

### Key Principles
1. **WordPress DB is authoritative**: All form data MUST be saved to WordPress DB
2. **External DB is optional**: External DB sync failures don't block submissions
3. **Transparent to users**: Users always get success message if WordPress succeeds
4. **Visible to admins**: Admins can see external DB sync failures in admin panel

## Code Flow

### 1. Form Submission Handler
**File**: `admin/ajax-handlers.php`
**Function**: `vas_dinamico_submit_form_handler()`
**Lines**: 89-254

```php
// Step 1: Prepare data
$data = array(
    'form_id' => $stable_form_id,
    'participant_id' => $participant_id,
    'form_name' => $form_name,
    // ... other fields
);

// Step 2: Always save to WordPress DB first
$wpdb_result = $wpdb->insert($table_name, $data, $formats);

// Step 3: If WordPress DB fails, stop here
if ($wpdb_result === false) {
    wp_send_json_error(array(
        'message' => 'Failed to submit form'
    ));
    return;
}

// Step 4: If external DB is enabled, try to sync
if ($external_db_enabled) {
    $result = $db_helper->insert_form_submission($data);
    
    if ($result['success']) {
        // Both databases succeeded
        $external_db_success = true;
    } else {
        // WordPress succeeded, external failed
        // Log error but continue
        $db_helper->record_error($result['error'], $result['error_code']);
    }
}

// Step 5: Return success (WordPress succeeded)
wp_send_json_success(array(
    'message' => 'Form submitted successfully',
    'wordpress_db' => true,
    'external_db_success' => $external_db_success
));
```

### 2. External Database Helper
**File**: `admin/database.php`
**Class**: `EIPSI_External_Database`

**Key Methods**:

#### `is_enabled()`
Checks if external DB is enabled and configured
```php
public function is_enabled() {
    $enabled = get_option('eipsi_external_db_enabled', false);
    if (!$enabled) return false;
    
    $credentials = $this->get_credentials();
    return !empty($credentials);
}
```

#### `insert_form_submission($data)`
Inserts data into external database
```php
public function insert_form_submission($data) {
    $mysqli = $this->get_connection();
    if (!$mysqli) {
        return array(
            'success' => false,
            'error' => 'Connection failed'
        );
    }
    
    // Ensure schema is ready (create table, add columns)
    $schema_result = $this->ensure_schema_ready($mysqli);
    if (!$schema_result['success']) {
        return array(
            'success' => false,
            'error' => $schema_result['error']
        );
    }
    
    // Insert data
    $stmt = $mysqli->prepare("INSERT INTO ...");
    $success = $stmt->execute();
    
    return array(
        'success' => $success,
        'insert_id' => $mysqli->insert_id
    );
}
```

#### `ensure_schema_ready($mysqli)`
Auto-creates table and adds missing columns
```php
private function ensure_schema_ready($mysqli) {
    // Create table if missing
    $this->create_table_if_missing($mysqli);
    
    // Resolve table name (with or without prefix)
    $table_name = $this->resolve_table_name($mysqli);
    
    // Add missing columns
    $this->ensure_required_columns($mysqli, $table_name);
    
    return array(
        'success' => true,
        'table_name' => $table_name
    );
}
```

## Error Handling

### WordPress Database Errors
**Behavior**: Submission fails completely
**User Impact**: Error message shown, form not submitted
**Admin Impact**: Error logged to debug.log

```php
if ($wpdb_result === false) {
    error_log('EIPSI Forms: WordPress DB insert failed - ' . $wpdb->last_error);
    wp_send_json_error(array(
        'message' => 'Failed to submit form. Please try again.'
    ));
    return; // Stop execution
}
```

### External Database Errors
**Behavior**: Submission succeeds (WordPress DB has data)
**User Impact**: Success message shown (optionally with warning)
**Admin Impact**: Error recorded, visible in admin panel

```php
if (!$result['success']) {
    // Log error
    error_log('EIPSI Forms: External DB insert failed - ' . $result['error']);
    
    // Record for admin
    $db_helper->record_error($result['error'], $result['error_code']);
    
    // Continue anyway (WordPress succeeded)
    $external_db_success = false;
}
```

## Configuration UI

### File: `admin/configuration.php`
**Function**: `eipsi_display_configuration_page()`

**Key UI Elements**:
1. Database indicator banner (shows current storage location)
2. Connection settings form (host, user, password, database name)
3. Test Connection button (verifies credentials and schema)
4. Save Configuration button (enables external DB)
5. Status section (shows connection status, record count, errors)

**Workflow**:
1. Admin enters credentials
2. Admin clicks "Test Connection"
3. Plugin verifies connection and auto-creates table
4. Admin clicks "Save Configuration"
5. External DB sync is enabled
6. Future submissions go to both databases

## Monitoring & Diagnostics

### Admin Panel
**Location**: WordPress Admin → Database Configuration

**Information Displayed**:
- Current storage location (WordPress only vs. Both databases)
- Connection status (Connected/Disconnected)
- Record count in current database
- Last sync error (if any)
- Error code and timestamp

### Debug Logging
Enable `WP_DEBUG` in `wp-config.php` to see detailed logs:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Log Messages**:
- `EIPSI Forms: Successfully saved to both WordPress DB (ID: X) and External DB (ID: Y)`
- `EIPSI Forms: External DB insert failed (WordPress DB succeeded) - [error message]`
- `EIPSI Forms: WordPress DB insert failed - [error message]`

### Error Retrieval
```php
require_once VAS_DINAMICO_PLUGIN_DIR . 'admin/database.php';
$db_helper = new EIPSI_External_Database();
$status = $db_helper->get_status();

echo $status['last_error']; // Error message
echo $status['last_error_code']; // Error code
echo $status['last_error_time']; // Timestamp
```

## Testing

### Test Case 1: WordPress DB Only
```
Given: External DB is disabled
When: User submits form
Then: Data saved to WordPress DB only
And: Success message shown
And: No external DB sync attempted
```

### Test Case 2: Both Databases Working
```
Given: External DB is enabled and working
When: User submits form
Then: Data saved to WordPress DB
And: Data saved to external DB
And: Success message shown
And: No errors logged
```

### Test Case 3: External DB Failure
```
Given: External DB is enabled but unreachable
When: User submits form
Then: Data saved to WordPress DB
And: External DB insert fails
And: Success message shown (optionally with warning)
And: Error logged for admin
```

### Test Case 4: WordPress DB Failure (Critical)
```
Given: WordPress DB is down
When: User submits form
Then: Form submission fails
And: Error message shown to user
And: No data saved anywhere
```

## Extending the System

### Adding a Third Database
To add another database (e.g., analytics database):

1. Create new helper class (e.g., `EIPSI_Analytics_Database`)
2. Add configuration in admin panel
3. Modify `vas_dinamico_submit_form_handler()`:

```php
// After WordPress and external DB
if ($analytics_db_enabled) {
    $analytics_result = $analytics_helper->insert_form_submission($data);
    if (!$analytics_result['success']) {
        // Log error but continue
        error_log('Analytics DB sync failed');
    }
}
```

### Adding Retry Mechanism
To retry failed external DB inserts:

1. Create queue table in WordPress DB
2. When external DB fails, add to queue
3. Create WP-Cron job to retry queued inserts
4. Remove from queue on success

```php
// In ajax-handlers.php
if (!$result['success']) {
    // Add to retry queue
    $queue_helper->add_to_queue($data, 'external_db');
}

// In cron job
function eipsi_retry_external_db_syncs() {
    $queue_helper = new EIPSI_Sync_Queue();
    $pending = $queue_helper->get_pending('external_db');
    
    foreach ($pending as $item) {
        $result = $db_helper->insert_form_submission($item['data']);
        if ($result['success']) {
            $queue_helper->mark_complete($item['id']);
        }
    }
}
```

### Adding Bulk Sync Tool
To sync existing WordPress data to external DB:

1. Create admin page for bulk sync
2. Query WordPress DB for unsynchronized records
3. Insert into external DB in batches
4. Show progress bar and status

```php
function eipsi_bulk_sync_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vas_form_results';
    
    // Get count of WordPress records
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    
    // Process in batches
    $batch_size = 100;
    for ($offset = 0; $offset < $total; $offset += $batch_size) {
        $records = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name LIMIT %d OFFSET %d",
                $batch_size,
                $offset
            )
        );
        
        foreach ($records as $record) {
            $db_helper->insert_form_submission((array) $record);
        }
        
        // Show progress
        $progress = min(100, ($offset / $total) * 100);
        echo "Progress: {$progress}%\n";
    }
}
```

## Best Practices

### 1. Always Validate WordPress DB First
Never skip WordPress DB insert. It's the source of truth.

### 2. Never Block User on External DB Failures
External DB is supplementary. Don't degrade user experience.

### 3. Log Everything
Both successes and failures should be logged for audit trail.

### 4. Test Connection Before Enabling
Always run connection test before saving configuration.

### 5. Monitor External DB Sync Health
Regularly check admin panel for sync errors.

### 6. Keep Schemas Identical
WordPress and external DB should have matching schemas.

## Security Considerations

### 1. Credential Encryption
External DB credentials are encrypted using WordPress salts:
```php
private function encrypt_data($data) {
    $key = wp_salt('auth');
    $iv = openssl_random_pseudo_bytes($iv_length);
    return base64_encode($iv . '::' . openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv));
}
```

### 2. Capability Checks
Only admins can configure external DB:
```php
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions');
}
```

### 3. Nonce Verification
AJAX requests are protected with nonces:
```php
check_ajax_referer('eipsi_admin_nonce', 'nonce');
```

### 4. SQL Injection Prevention
All queries use prepared statements:
```php
$stmt = $mysqli->prepare("INSERT INTO ... VALUES (?, ?, ?)");
$stmt->bind_param('sss', $var1, $var2, $var3);
```

## Troubleshooting

### Problem: External DB sync always fails
**Solution**: Check connection credentials, verify external DB is reachable, check firewall rules

### Problem: WordPress DB insert fails
**Solution**: Check WordPress DB connection, verify table exists, check permissions

### Problem: Table not auto-created
**Solution**: Verify user has CREATE TABLE permission, check error logs

### Problem: Missing columns not added
**Solution**: Verify user has ALTER TABLE permission, manually run upgrade

### Problem: Performance degradation
**Solution**: Optimize external DB connection, consider connection pooling, add indexes

## Performance Optimization

### 1. Connection Reuse
Reuse external DB connections within same request:
```php
private static $connection = null;

public function get_connection() {
    if (self::$connection !== null) {
        return self::$connection;
    }
    // ... create connection
    self::$connection = $mysqli;
    return $mysqli;
}
```

### 2. Async Processing
For high-traffic sites, consider async external DB inserts:
```php
// Add to queue immediately
wp_schedule_single_event(time(), 'eipsi_process_external_db_insert', array($data));

// Process later
add_action('eipsi_process_external_db_insert', function($data) {
    $db_helper->insert_form_submission($data);
});
```

### 3. Batch Processing
For multiple submissions, batch insert:
```php
// Accumulate submissions
$batch = array();
foreach ($submissions as $submission) {
    $batch[] = $submission;
    
    if (count($batch) >= 10) {
        $db_helper->batch_insert_form_submissions($batch);
        $batch = array();
    }
}
```

## Maintenance

### Regular Tasks
1. Monitor external DB sync health weekly
2. Review error logs for patterns
3. Verify both databases have matching data
4. Update credentials if changed
5. Test connection after external DB maintenance

### Schema Updates
When adding new columns:
1. Update `vas_dinamico_activate()` in main plugin file
2. Update `ensure_required_columns()` in database.php
3. Increment database version
4. Test upgrade on staging first

## Conclusion

The dual-database write system ensures maximum reliability by:
- Always saving to WordPress DB (authoritative source)
- Optionally syncing to external DB (supplementary storage)
- Gracefully handling external DB failures
- Providing visibility into sync status

This architecture balances data redundancy with user experience, ensuring form submissions are never lost while providing flexibility for external data storage.
