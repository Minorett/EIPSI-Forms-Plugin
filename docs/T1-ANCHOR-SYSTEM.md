# T1-Anchor System (v2.6.0)

## Overview

The T1-Anchor system transforms EIPSI Forms longitudinal studies from "calculate dates on the fly" to "calculate once, persist forever." When a participant completes T1 (wave_index=1), all future wave dates are calculated and stored as absolute timestamps.

## Key Benefits

1. **Determinism**: Dates are calculated once and persisted
2. **Auditability**: Each participant has their complete timeline recorded
3. **Scalability**: Cron jobs do simple timestamp comparisons (`NOW() > due_at`)
4. **Flexibility**: Each participant gets their own timeline based on when they completed T1

## Database Changes (Phase 1)

### New Columns

| Table | Column | Type | Description |
|-------|--------|------|-------------|
| `survey_waves` | `offset_minutes` | INT(11) | Minutes after T1 completion when wave becomes available |
| `survey_waves` | `window_minutes` | INT(11) NULL | Minutes the wave stays open (NULL = until next wave) |
| `survey_studies` | `study_end_offset_minutes` | INT(11) NULL | Minutes after T1 when entire study closes |
| `survey_participants` | `t1_completed_at` | DATETIME | Anchor timestamp: when participant completed T1 |

### Running the Migration

```php
// Via AJAX (admin panel)
wp_ajax_eipsi_run_offset_migration

// Via CLI
wp eval-file scripts/migration-add-offset-columns.php
```

## How It Works (Phase 2)

### 1. Wave Configuration

When configuring waves, set `offset_minutes` relative to T1:

```
T1: offset_minutes = 0      (available immediately)
T2: offset_minutes = 10080  (available 7 days after T1)
T3: offset_minutes = 20160  (available 14 days after T1)
T4: offset_minutes = 43200  (available 30 days after T1)
```

### 2. T1 Completion Triggers Anchoring

When a participant submits T1:

```php
// Automatically hooked via:
add_action('eipsi_form_submitted', array('EIPSI_T1_Anchor_Service', 'on_form_submitted'));
```

The service:
1. Records `t1_completed_at` on the participant
2. Calculates `available_at` and `due_at` for each wave
3. Persists these dates to `survey_assignments`

### 3. Cron Processing

Two cron jobs run every 5 minutes:

- **`eipsi_process_assignment_expirations`**: Marks assignments as `expired` where `NOW() > due_at`
- **`eipsi_process_wave_availability`**: Sends notifications when waves become available

## API Reference

### Anchor a Participant's Timeline

```php
EIPSI_T1_Anchor_Service::anchor_participant_timeline($study_id, $participant_id);
```

### Get Anchored Timeline

```php
$timeline = EIPSI_T1_Anchor_Service::get_participant_anchored_timeline($study_id, $participant_id);

// Returns:
[
    'success' => true,
    'participant' => [
        'id' => 123,
        't1_completed_at' => '2026-04-28 10:30:00',
        'is_anchored' => true,
    ],
    'timeline' => [
        [
            'wave_index' => 1,
            'status' => 'submitted',
            'available_at' => '2026-04-28 10:30:00',
            'due_at' => '2026-04-28 10:30:00',
        ],
        [
            'wave_index' => 2,
            'status' => 'pending',
            'available_at' => '2026-05-05 10:30:00',
            'due_at' => '2026-05-12 10:30:00',
            'visual_status' => 'pending',
            'time_until_open' => 604800, // seconds
        ],
        // ...
    ],
    'active_wave' => [...],
    'study_completed' => false,
]
```

### Manual Anchoring

```php
// For existing participants who completed T1 before the system was in place
EIPSI_T1_Anchor_Service::manual_anchor($study_id, $participant_id, $t1_timestamp, $force);
```

### Batch Anchoring

```php
// Anchor all unanchored participants who have submitted T1
EIPSI_T1_Anchor_Service::batch_anchor_existing_participants($study_id);
```

## AJAX Endpoints

| Action | Description |
|--------|-------------|
| `eipsi_run_offset_migration` | Run database migration |
| `eipsi_batch_anchor_participants` | Batch anchor existing participants |
| `eipsi_manual_anchor_participant` | Manually anchor a specific participant |
| `eipsi_get_anchored_timeline` | Get participant's anchored timeline |
| `eipsi_check_offset_migration_status` | Check if migration has been applied |

## Example: Complete Flow

```
1. Study created with 4 waves:
   - T1: offset_minutes = 0
   - T2: offset_minutes = 10080 (7 days)
   - T3: offset_minutes = 20160 (14 days)
   - T4: offset_minutes = 43200 (30 days)
   - study_end_offset_minutes = 50400 (35 days)

2. Participant registers: 2026-04-28 10:00:00
   - T1 assignment created (available_at = NULL, due_at = NULL)

3. Participant completes T1: 2026-04-28 10:30:00
   - t1_completed_at = '2026-04-28 10:30:00'
   - T1 assignment: available_at = '2026-04-28 10:30:00', due_at = '2026-04-28 10:30:00', status = 'submitted'
   - T2 assignment: available_at = '2026-05-05 10:30:00', due_at = '2026-05-12 10:30:00'
   - T3 assignment: available_at = '2026-05-12 10:30:00', due_at = '2026-05-28 10:30:00'
   - T4 assignment: available_at = '2026-05-28 10:30:00', due_at = '2026-06-02 10:30:00'

4. Cron runs at 2026-05-05 10:35:00:
   - T2 is now available (NOW >= available_at)
   - Notification sent to participant

5. T2 not completed by 2026-05-12 10:30:00:
   - Cron marks T2 as 'expired'
   - Audit log entry created
```

## Upgrade Path

For existing studies with participants:

1. Run the migration: `eipsi_run_offset_migration`
2. Configure `offset_minutes` for each wave
3. Batch anchor existing participants: `eipsi_batch_anchor_participants`

The system will:
- NOT touch waves that are already `submitted` or `expired`
- Only calculate dates for `pending` or `in_progress` assignments
- Log all changes to the audit table
