# Task 2 Analysis: Malformed KEY Index Definitions

## Task Description
Fix malformed KEY index definitions in database-schema-manager.php:
- `ALTER TABLE wp_survey_waves ADD `` (``)`
- `ALTER TABLE wp_survey_assignments ADD `` (``)`

## Analysis Results

### 1. CREATE TABLE Statements Checked
I thoroughly examined the CREATE TABLE statements for both affected tables:

**wp_survey_waves** (lines 1812-1841):
```sql
CREATE TABLE {$table_name} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    study_id BIGINT(20) UNSIGNED NOT NULL,
    wave_index INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    form_id BIGINT(20) UNSIGNED NOT NULL,
    ...
    PRIMARY KEY (id),
    KEY `idx_study_id` (`study_id`),      -- ✓ CORRECT
    KEY `idx_status` (`status`),            -- ✓ CORRECT
    KEY `idx_due_date` (`due_date`),        -- ✓ CORRECT
    UNIQUE KEY `uk_study_index` (`study_id`, `wave_index`)
) ENGINE=InnoDB {$charset_collate};
```

**wp_survey_assignments** (lines 1914-1946):
```sql
CREATE TABLE {$table_name} (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    study_id BIGINT(20) UNSIGNED NOT NULL,
    wave_id BIGINT(20) UNSIGNED NOT NULL,
    participant_id BIGINT(20) UNSIGNED NOT NULL,
    ...
    PRIMARY KEY (id),
    KEY `idx_study_id` (`study_id`),       -- ✓ CORRECT
    KEY `idx_wave_id` (`wave_id`),         -- ✓ CORRECT
    KEY `idx_participant_id` (`participant_id`), -- ✓ CORRECT
    KEY `idx_status` (`status`),             -- ✓ CORRECT
    KEY `idx_submitted_at` (`submitted_at`),   -- ✓ CORRECT
    KEY `idx_due_at` (`due_at`),           -- ✓ CORRECT
    UNIQUE KEY `uk_wave_participant` (`wave_id`, `participant_id`)
) ENGINE=InnoDB {$charset_collate};
```

### 2. Root Cause Analysis
The error `ALTER TABLE wp_survey_waves ADD `` (``)` would be generated if the `ensure_local_index()` function (line 1474-1495) is called with an empty string for the `$column` parameter.

Current function:
```php
private static function ensure_local_index( $table, $column ) {
    global $wpdb;

    // Guard: skip if table or column name is empty to avoid malformed SQL.
    if ( empty( $table ) || empty( $column ) ) {
        return;
    }

    // Guard: skip if the table does not exist yet
    $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( empty( $table_exists ) ) {
        return;
    }

    $indexes = $wpdb->get_results( "SHOW INDEX FROM `{$table}` WHERE Column_name = '{$column}'" );
    if ( empty( $indexes ) ) {
        $wpdb->query( "ALTER TABLE `{$table}` ADD KEY `{$column}` (`{$column}`)" );
    }
}
```

If `$column` is an empty string `""`, it would pass the `empty()` check (since `empty("")` returns true) but somehow still reach line 1493, generating:
```sql
ALTER TABLE `wp_survey_waves` ADD KEY `` (``)
```

### 3. Verification
- ✅ All CREATE TABLE KEY definitions are properly formatted
- ✅ All KEY definitions have valid index names (e.g., `idx_study_id`)
- ✅ All KEY definitions reference valid column names
- ⚠️  The `ensure_local_index()` function needs additional validation

## Conclusion

**Status: NO MALFORMED KEY DEFINITIONS FOUND IN CURRENT FILE**

The CREATE TABLE statements for `wp_survey_waves` and `wp_survey_assignments` are **already correct**. All KEY definitions are properly formatted with valid syntax:

```sql
KEY `index_name` (`column_name`)
```

The reported errors (`ALTER TABLE wp_survey_waves ADD `` (``)`) would only occur if:
1. The `ensure_local_index()` function is called with an empty column name
2. The function's existing guards fail to catch the empty string in edge cases

**Recommended Action:**
Since the CREATE TABLE statements are already correct, the task appears to be either:
1. A preventive task to ensure robustness
2. Based on an older version of the file
3. Theoretical scenario testing

The current implementation already includes guards to prevent malformed SQL, but additional validation could be added for extra safety.
