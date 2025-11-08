-- EIPSI Forms Tracking Database Queries
-- These queries help verify the tracking implementation and analyze data

-- ============================================================================
-- TABLE STRUCTURE VERIFICATION
-- ============================================================================

-- Check if the tracking table exists
SHOW TABLES LIKE '%vas_form_events%';

-- View table structure
DESCRIBE wp_vas_form_events;

-- View table indexes
SHOW INDEX FROM wp_vas_form_events;

-- ============================================================================
-- DATA VERIFICATION QUERIES
-- ============================================================================

-- View all tracking events (most recent first)
SELECT 
    id,
    form_id,
    LEFT(session_id, 16) as session,
    event_type,
    page_number,
    LEFT(user_agent, 50) as browser,
    created_at
FROM wp_vas_form_events
ORDER BY created_at DESC
LIMIT 20;

-- Count total events
SELECT COUNT(*) as total_events FROM wp_vas_form_events;

-- Count events by type
SELECT 
    event_type,
    COUNT(*) as count
FROM wp_vas_form_events
GROUP BY event_type
ORDER BY count DESC;

-- Count events by form
SELECT 
    form_id,
    COUNT(*) as event_count,
    COUNT(DISTINCT session_id) as unique_sessions
FROM wp_vas_form_events
GROUP BY form_id
ORDER BY event_count DESC;

-- ============================================================================
-- ANALYTICS QUERIES
-- ============================================================================

-- Form completion funnel
SELECT 
    COUNT(DISTINCT CASE WHEN event_type = 'view' THEN session_id END) as views,
    COUNT(DISTINCT CASE WHEN event_type = 'start' THEN session_id END) as starts,
    COUNT(DISTINCT CASE WHEN event_type = 'submit' THEN session_id END) as submits,
    COUNT(DISTINCT CASE WHEN event_type = 'abandon' THEN session_id END) as abandons,
    ROUND(
        COUNT(DISTINCT CASE WHEN event_type = 'start' THEN session_id END) * 100.0 /
        NULLIF(COUNT(DISTINCT CASE WHEN event_type = 'view' THEN session_id END), 0),
        2
    ) as start_rate,
    ROUND(
        COUNT(DISTINCT CASE WHEN event_type = 'submit' THEN session_id END) * 100.0 /
        NULLIF(COUNT(DISTINCT CASE WHEN event_type = 'view' THEN session_id END), 0),
        2
    ) as completion_rate
FROM wp_vas_form_events;

-- Completion rate by form
SELECT 
    form_id,
    COUNT(DISTINCT CASE WHEN event_type = 'view' THEN session_id END) as views,
    COUNT(DISTINCT CASE WHEN event_type = 'submit' THEN session_id END) as submits,
    ROUND(
        COUNT(DISTINCT CASE WHEN event_type = 'submit' THEN session_id END) * 100.0 /
        NULLIF(COUNT(DISTINCT CASE WHEN event_type = 'view' THEN session_id END), 0),
        2
    ) as completion_rate
FROM wp_vas_form_events
GROUP BY form_id
ORDER BY views DESC;

-- Abandonment analysis (where users drop off)
SELECT 
    event_type,
    page_number,
    COUNT(DISTINCT session_id) as sessions
FROM wp_vas_form_events
WHERE event_type IN ('page_change', 'abandon')
GROUP BY event_type, page_number
ORDER BY page_number, event_type;

-- Session timeline (trace individual user journey)
SELECT 
    event_type,
    page_number,
    created_at,
    TIMESTAMPDIFF(SECOND, 
        LAG(created_at) OVER (ORDER BY created_at), 
        created_at
    ) as seconds_since_previous
FROM wp_vas_form_events
WHERE session_id = 'YOUR_SESSION_ID_HERE'
ORDER BY created_at;

-- Events per hour (activity patterns)
SELECT 
    HOUR(created_at) as hour_of_day,
    COUNT(*) as event_count
FROM wp_vas_form_events
GROUP BY hour_of_day
ORDER BY hour_of_day;

-- Events per day (trend analysis)
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_events,
    COUNT(DISTINCT session_id) as unique_sessions,
    COUNT(DISTINCT form_id) as forms_used
FROM wp_vas_form_events
GROUP BY date
ORDER BY date DESC
LIMIT 30;

-- Most recent sessions with full event timeline
SELECT 
    session_id,
    form_id,
    GROUP_CONCAT(event_type ORDER BY created_at SEPARATOR ' → ') as event_flow,
    MIN(created_at) as session_start,
    MAX(created_at) as session_end,
    TIMESTAMPDIFF(SECOND, MIN(created_at), MAX(created_at)) as duration_seconds
FROM wp_vas_form_events
GROUP BY session_id, form_id
ORDER BY session_start DESC
LIMIT 20;

-- Identify incomplete sessions (viewed but not submitted)
SELECT 
    e.session_id,
    e.form_id,
    MIN(e.created_at) as first_event,
    MAX(e.created_at) as last_event,
    GROUP_CONCAT(DISTINCT e.event_type ORDER BY e.created_at) as events
FROM wp_vas_form_events e
WHERE e.session_id IN (
    SELECT session_id FROM wp_vas_form_events WHERE event_type = 'view'
)
AND e.session_id NOT IN (
    SELECT session_id FROM wp_vas_form_events WHERE event_type = 'submit'
)
GROUP BY e.session_id, e.form_id
ORDER BY last_event DESC
LIMIT 20;

-- ============================================================================
-- DATA CLEANUP QUERIES
-- ============================================================================

-- Delete events older than 90 days (adjust as needed)
-- DELETE FROM wp_vas_form_events 
-- WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Delete test events (if you used test form IDs)
-- DELETE FROM wp_vas_form_events 
-- WHERE form_id LIKE 'test%' OR form_id = '';

-- ============================================================================
-- PERFORMANCE QUERIES
-- ============================================================================

-- Check table size
SELECT 
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
    table_rows AS 'Rows'
FROM information_schema.TABLES
WHERE table_schema = DATABASE()
AND table_name = 'wp_vas_form_events';

-- Index usage statistics (requires MySQL 5.6+)
SELECT 
    INDEX_NAME,
    SEQ_IN_INDEX,
    COLUMN_NAME,
    CARDINALITY
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'wp_vas_form_events'
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

-- ============================================================================
-- EXPORT QUERIES FOR RESEARCH
-- ============================================================================

-- Export all events for a specific form (CSV-ready)
SELECT 
    id,
    form_id,
    session_id,
    event_type,
    page_number,
    user_agent,
    created_at
FROM wp_vas_form_events
WHERE form_id = 'YOUR_FORM_ID'
ORDER BY session_id, created_at;

-- Export session summaries for research analysis
SELECT 
    session_id,
    form_id,
    MIN(CASE WHEN event_type = 'view' THEN created_at END) as viewed_at,
    MIN(CASE WHEN event_type = 'start' THEN created_at END) as started_at,
    MAX(CASE WHEN event_type = 'page_change' THEN page_number END) as max_page_reached,
    MIN(CASE WHEN event_type = 'submit' THEN created_at END) as submitted_at,
    MIN(CASE WHEN event_type = 'abandon' THEN created_at END) as abandoned_at,
    TIMESTAMPDIFF(
        SECOND,
        MIN(created_at),
        MAX(created_at)
    ) as total_duration_seconds,
    COUNT(*) as total_events
FROM wp_vas_form_events
GROUP BY session_id, form_id
ORDER BY MIN(created_at) DESC;

-- ============================================================================
-- NOTES:
-- ============================================================================
-- 1. Replace 'wp_' with your actual WordPress table prefix if different
-- 2. Replace 'YOUR_SESSION_ID_HERE' with actual session ID for session timeline
-- 3. Replace 'YOUR_FORM_ID' with actual form ID for form-specific queries
-- 4. Uncomment DELETE queries only when you're sure you want to remove data
-- 5. Use LIMIT clauses to avoid overwhelming results in large datasets
-- ============================================================================
