# PHASE 5.1: EIPSI_Anonymize_Service - Testing Manual

**Fecha:** 2025-02-05
**Versión:** v1.4.2
**Objetivo:** Validar implementación completa del servicio de anonimización y auditoría

---

## 📋 Checklist General

- [ ] Tabla `wp_survey_audit_log` creada correctamente
- [ ] Todas las columnas presentes (`actor_username`, `ip_address`)
- [ ] Índices compuestos creados (`idx_survey_action`, `idx_survey_created`, `idx_action_created`)
- [ ] Foreign keys creadas (`fk_audit_survey`, `fk_audit_participant`)
- [ ] Todos los métodos implementados (0 TODOs restantes)
- [ ] Anonimización de survey completa funciona
- [ ] Anonimización de participante individual funciona
- [ ] Magic links se invalidan correctamente
- [ ] Audit log registra todas las acciones
- [ ] Permisos validados correctamente

---

## 🧪 Test 1: Creación de Tabla `wp_survey_audit_log`

### Preparación
```sql
DROP TABLE IF EXISTS wp_survey_audit_log;
```

### Ejecución
Activar el plugin o llamar manualmente a la sincronización:

```php
$result = EIPSI_Database_Schema_Manager::verify_and_sync_schema();
```

### Verificación esperada

```sql
DESCRIBE wp_survey_audit_log;
```

Debería mostrar:
```
Field             Type                  Null    Key     Default             Extra
----------------- --------------------- ------- ------- ------------------- ------------------
id                bigint(20) unsigned   NO      PRI     NULL                auto_increment
survey_id         bigint(20) unsigned   NO      MUL     NULL
participant_id    bigint(20) unsigned   YES     MUL     NULL
action            varchar(100)          NO      MUL     NULL
actor_type        enum('admin','system')YES             system
actor_id          bigint(20) unsigned   YES             NULL
actor_username    varchar(255)          YES             NULL
ip_address        varchar(45)           YES             NULL
metadata          json                  YES             NULL
created_at        datetime             YES             CURRENT_TIMESTAMP
```

### Verificación de índices
```sql
SHOW INDEX FROM wp_survey_audit_log;
```

Debería mostrar:
```
Table                  Key_name            Column_name         Seq  Index_type
---------------------- ------------------- ------------------- ---- ----------
wp_survey_audit_log    PRIMARY             id                  1    BTREE
wp_survey_audit_log    idx_survey_action   survey_id           1    BTREE
wp_survey_audit_log    idx_survey_action   action              2    BTREE
wp_survey_audit_log    idx_survey_created  survey_id           1    BTREE
wp_survey_audit_log    idx_survey_created  created_at          2    BTREE
wp_survey_audit_log    idx_action_created  action              1    BTREE
wp_survey_audit_log    idx_action_created  created_at          2    BTREE
wp_survey_audit_log    idx_participant_id  participant_id      1    BTREE
```

### Verificación de Foreign Keys (best effort)
```sql
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'wp_survey_audit_log'
  AND REFERENCED_TABLE_NAME IS NOT NULL;
```

Debería mostrar (si el motor DB las soporta):
```
CONSTRAINT_NAME       TABLE_NAME               COLUMN_NAME      REFERENCED_TABLE_NAME   REFERENCED_COLUMN_NAME
--------------------- ------------------------ --------------- ---------------------- -----------------------
fk_audit_survey       wp_survey_audit_log      survey_id       wp_posts               ID
fk_audit_participant  wp_survey_audit_log      participant_id  wp_survey_participants id
```

---

## 🧪 Test 2: Anonimizar Survey Completo

### Preparación
Crear survey de prueba con participantes:

```php
// Crear survey (usando post_type 'survey')
$survey_id = wp_insert_post(array(
    'post_title' => 'Test Survey for Anonymization',
    'post_type' => 'survey',
    'post_status' => 'publish'
));

// Crear 5 participantes de prueba
for ($i = 1; $i <= 5; $i++) {
    EIPSI_Participant_Service::create_participant(
        $survey_id,
        "participant{$i}@example.com",
        "password123456",
        array(
            'first_name' => "Participant",
            'last_name' => "Number{$i}"
        )
    );
}
```

### Verificación inicial
```sql
SELECT id, email, first_name, last_name, is_active
FROM wp_survey_participants
WHERE survey_id = {survey_id};
```

Debería mostrar:
```
id  email                        first_name  last_name   is_active
--- ---------------------------- ----------- ---------- ----------
1   participant1@example.com     Participant Number1     1
2   participant2@example.com     Participant Number2     1
3   participant3@example.com     Participant Number3     1
4   participant4@example.com     Participant Number4     1
5   participant5@example.com     Participant Number5     1
```

### Ejecución de anonimización
```php
// Primero crear assignments con status 'submitted' para permitir anonimización
$participants = EIPSI_Participant_Service::list_participants($survey_id);
foreach ($participants['participants'] as $participant) {
    $wpdb->insert(
        $wpdb->prefix . 'survey_assignments',
        array(
            'study_id' => $survey_id,
            'wave_id' => 1,
            'participant_id' => $participant->id,
            'status' => 'submitted',
            'submitted_at' => current_time('mysql')
        )
    );
}

// Anonimizar survey
$result = EIPSI_Anonymize_Service::anonymize_survey($survey_id, 'Testing anonymization feature');

print_r($result);
```

### Resultado esperado
```php
Array (
    [success] => 1
    [anonymized_count] => 5
    [error] => null
)
```

### Verificación post-anonimización

#### 1. PII borrado de participantes
```sql
SELECT id, email, first_name, last_name, is_active, JSON_EXTRACT(metadata, '$.pii_deleted_at') AS pii_deleted_at
FROM wp_survey_participants
WHERE survey_id = {survey_id};
```

Debería mostrar:
```
id  email                      first_name  last_name   is_active  pii_deleted_at
--- -------------------------- ----------- ---------- ---------- ------------------
1   anonymous_1@deleted.local  NULL        NULL        0          2025-02-05 12:00:00
2   anonymous_2@deleted.local  NULL        NULL        0          2025-02-05 12:00:00
3   anonymous_3@deleted.local  NULL        NULL        0          2025-02-05 12:00:00
4   anonymous_4@deleted.local  NULL        NULL        0          2025-02-05 12:00:00
5   anonymous_5@deleted.local  NULL        NULL        0          2025-02-05 12:00:00
```

#### 2. Magic links invalidados
```sql
SELECT COUNT(*) AS invalidated_count
FROM wp_survey_magic_links
WHERE survey_id = {survey_id} AND used_at IS NOT NULL;
```

Debería mostrar:
```
invalidated_count
------------------
5 (o más si había links previos)
```

#### 3. Post meta de survey
```php
$is_anonymized = get_post_meta($survey_id, '_survey_anonymized', true);
$anonymized_at = get_post_meta($survey_id, '_anonymized_at', true);
$anonymized_by = get_post_meta($survey_id, '_anonymized_by_user', true);

echo "Anonymized: $is_anonymized\n";
echo "At: $anonymized_at\n";
echo "By user: $anonymized_by\n";
```

Debería mostrar:
```
Anonymized: 1
At: 2025-02-05 12:00:00
By user: 1
```

#### 4. Audit log poblado
```sql
SELECT action, actor_type, actor_username, ip_address, JSON_PRETTY(metadata) AS metadata, created_at
FROM wp_survey_audit_log
WHERE survey_id = {survey_id}
ORDER BY created_at DESC;
```

Debería mostrar:
```
action              actor_type  actor_username  ip_address    metadata                         created_at
------------------- ----------- -------------- ------------- -------------------------------- -----------------
anonymize_survey    admin       your_username  192.168.1.100 {"reason":"Testing anonymization feature", "anonymized_count":5, "active_count":5} 2025-02-05 12:00:00
```

---

## 🧪 Test 3: Anonimizar Participante Individual

### Preparación
Usar el survey del Test 2, pero crear un nuevo participante:

```php
$participant_id = EIPSI_Participant_Service::create_participant(
    $survey_id,
    "individual@example.com",
    "password123456",
    array(
        'first_name' => "Individual",
        'last_name' => "Test"
    )
);
echo "Created participant ID: $participant_id\n";
```

### Ejecución
```php
$result = EIPSI_Anonymize_Service::anonymize_participant($participant_id, 'Individual withdrawal');

print_r($result);
```

### Resultado esperado
```php
Array (
    [success] => 1
    [error] => null
)
```

### Verificación
```sql
SELECT id, email, first_name, last_name, is_active
FROM wp_survey_participants
WHERE id = {participant_id};
```

Debería mostrar:
```
id  email                              first_name  last_name  is_active
--- ---------------------------------- ----------- ---------- ----------
X   anonymous_X@deleted.local          NULL        NULL       0
```

### Verificación de audit log
```sql
SELECT action, participant_id, JSON_EXTRACT(metadata, '$.reason') AS reason
FROM wp_survey_audit_log
WHERE action = 'anonymize_participant' AND participant_id = {participant_id};
```

Debería mostrar:
```
action                  participant_id  reason
---------------------- --------------- ------------------------
anonymize_participant   X               Individual withdrawal
```

---

## 🧪 Test 4: Invalidar Magic Links

### Preparación
Crear magic links de prueba:

```php
// Crear magic link para participante
$token_plain = EIPSI_MagicLinksService::generate_magic_link($survey_id, $participant_id);
echo "Generated magic link token: $token_plain\n";
```

### Verificación de estado inicial
```sql
SELECT id, token_hash, used_at, expires_at
FROM wp_survey_magic_links
WHERE participant_id = {participant_id} AND used_at IS NULL;
```

Debería mostrar:
```
id  token_hash                                          used_at  expires_at
--- --------------------------------------------------- -------- ------------------
X   [sha256 hash del token]                              NULL     2025-02-07 12:00:00
```

### Ejecución de invalidación
```php
$count = EIPSI_Anonymize_Service::invalidate_participant_magic_links($participant_id);
echo "Invalidated $count magic links\n";
```

### Verificación post-invalidación
```sql
SELECT id, token_hash, used_at, expires_at
FROM wp_survey_magic_links
WHERE participant_id = {participant_id};
```

Debería mostrar:
```
id  token_hash                                          used_at              expires_at
--- --------------------------------------------------- -------------------- --------------------
X   [sha256 hash del token]                              2025-02-05 12:05:00  2025-02-05 12:05:00
```

---

## 🧪 Test 5: Audit Log Completo

### Preparación
Asegurarse de tener el survey y participantes de tests anteriores.

### Ejecución de múltiples acciones
```php
// Acción 1: Invalidar links
EIPSI_Anonymize_Service::invalidate_magic_links($survey_id);

// Acción 2: Anonimizar participante individual
EIPSI_Anonymize_Service::anonymize_participant($participant_id, 'Test');

// Acción 3: Anonimizar survey completo
EIPSI_Anonymize_Service::anonymize_survey($survey_id, 'Final test');
```

### Verificación de historial completo
```php
$log = EIPSI_Anonymize_Service::get_survey_audit_log($survey_id, 50);

echo "Audit log for survey $survey_id:\n";
echo str_repeat('-', 80) . "\n";

foreach ($log as $entry) {
    echo sprintf(
        "[%s] %s by %s (%s)\n",
        $entry->created_at,
        $entry->action,
        $entry->actor_username ?: 'system',
        $entry->ip_address ?: 'N/A'
    );

    if ($entry->metadata) {
        $metadata = json_decode($entry->metadata, true);
        if ($metadata) {
            foreach ($metadata as $key => $value) {
                echo "    - $key: $value\n";
            }
        }
    }
    echo "\n";
}
```

### Resultado esperado
```
Audit log for survey 123:
--------------------------------------------------------------------------------
[2025-02-05 12:10:00] anonymize_survey by your_username (192.168.1.100)
    - reason: Final test
    - anonymized_count: 5
    - active_count: 5

[2025-02-05 12:09:00] anonymize_participant by your_username (192.168.1.100)
    - reason: Test

[2025-02-05 12:08:00] invalidate_magic_links by your_username (192.168.1.100)

[2025-02-05 12:07:00] anonymize_survey by your_username (192.168.1.100)
    - reason: Testing anonymization feature
    - anonymized_count: 5
    - active_count: 5
```

---

## 🧪 Test 6: Validación de Permisos

### Escenario 1: Usuario sin permisos `manage_options`

```php
// Temporariamente cambiar usuario actual
wp_set_current_user(2); // Usuario sin 'manage_options'

$result = EIPSI_Anonymize_Service::anonymize_survey($survey_id, 'Test');

print_r($result);
```

### Resultado esperado
```php
Array (
    [success] => false
    [anonymized_count] => 0
    [error] => insufficient_permissions
)
```

### Verificación que NO se anonimizó
```sql
SELECT COUNT(*) AS still_active
FROM wp_survey_participants
WHERE survey_id = {survey_id} AND is_active = 1 AND email NOT LIKE '%@deleted.local';
```

Debería mostrar:
```
still_active
---
> 0 (los participantes siguen activos)
```

---

## 🧪 Test 7: `can_anonymize_survey()`

### Escenario 1: Survey listo para anonimizar

```php
// Crear survey con assignments 'submitted'
$survey_id = wp_insert_post(array(
    'post_title' => 'Ready Survey',
    'post_type' => 'survey',
    'post_status' => 'publish'
));

// Crear participante y assignment submitted
$participant_id = EIPSI_Participant_Service::create_participant($survey_id, "ready@test.com", "pass123", array());
$wpdb->insert(
    $wpdb->prefix . 'survey_assignments',
    array('study_id' => $survey_id, 'wave_id' => 1, 'participant_id' => $participant_id, 'status' => 'submitted')
);

$check = EIPSI_Anonymize_Service::can_anonymize_survey($survey_id);
print_r($check);
```

### Resultado esperado
```php
Array (
    [can_anonymize] => 1
    [reason] => Survey ready for anonymization
    [pending_count] => 0
    [submitted_count] => 1
)
```

### Escenario 2: Survey con assignments pendientes

```php
$wpdb->insert(
    $wpdb->prefix . 'survey_assignments',
    array('study_id' => $survey_id, 'wave_id' => 2, 'participant_id' => $participant_id, 'status' => 'pending')
);

$check = EIPSI_Anonymize_Service::can_anonymize_survey($survey_id);
print_r($check);
```

### Resultado esperado
```php
Array (
    [can_anonymize] =>
    [reason] => Survey has 1 pending or in-progress assignments
)
```

### Escenario 3: Survey ya anonimizado

```php
update_post_meta($survey_id, '_survey_anonymized', 1);

$check = EIPSI_Anonymize_Service::can_anonymize_survey($survey_id);
print_r($check);
```

### Resultado esperado
```php
Array (
    [can_anonymize] =>
    [reason] => Survey already anonymized
)
```

---

## 🧪 Test 8: Performance - Anonimizar 100+ participantes

### Preparación
```php
$survey_id = wp_insert_post(array(
    'post_title' => 'Performance Test Survey',
    'post_type' => 'survey',
    'post_status' => 'publish'
));

// Crear 150 participantes
$start_time = microtime(true);

for ($i = 1; $i <= 150; $i++) {
    EIPSI_Participant_Service::create_participant(
        $survey_id,
        "perf{$i}@test.com",
        "pass123456",
        array('first_name' => "Perf", 'last_name' => "Test{$i}")
    );
}

// Crear assignments submitted para todos
$participants = $wpdb->get_col(
    $wpdb->prepare("SELECT id FROM {$wpdb->prefix}survey_participants WHERE survey_id = %d", $survey_id)
);

foreach ($participants as $participant_id) {
    $wpdb->insert(
        $wpdb->prefix . 'survey_assignments',
        array('study_id' => $survey_id, 'wave_id' => 1, 'participant_id' => $participant_id, 'status' => 'submitted')
    );
}
```

### Ejecución
```php
$anonymize_start = microtime(true);

$result = EIPSI_Anonymize_Service::anonymize_survey($survey_id, 'Performance test');

$anonymize_end = microtime(true);
$anonymize_time = $anonymize_end - $anonymize_start;

echo "Anonymized: {$result['anonymized_count']} participants\n";
echo "Time: " . number_format($anonymize_time, 3) . " seconds\n";
```

### Criterios de aceptación
- ✅ Todos los 150 participantes anonimizados
- ✅ Tiempo total < 2 segundos
- ✅ Sin errores en log de PHP

---

## 🧪 Test 9: Seguridad - Prepared Statements

### Verificación manual
Revisar el código fuente de `class-anonymize-service.php` y confirmar que:

1. **TODAS las queries SQL usan `$wpdb->prepare()`**

   ```php
   // ✅ CORRECTO
   $wpdb->get_var($wpdb->prepare(
       "SELECT * FROM {$table} WHERE id = %d AND email = %s",
       $id,
       $email
   ));

   // ❌ INCORRECTO
   $wpdb->get_var("SELECT * FROM {$table} WHERE id = $id AND email = '$email'");
   ```

2. **IDs siempre validados con `intval()`**

   ```php
   // ✅ CORRECTO
   $survey_id = intval($survey_id);
   if ($survey_id <= 0) { return false; }

   // ❌ INCORRECTO
   // Uso directo sin validación
   ```

3. **Strings sanitizados con `sanitize_text_field()`**

   ```php
   // ✅ CORRECTO
   $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : null;

   // ❌ INCORRECTO
   $ip_address = $_SERVER['REMOTE_ADDR'];
   ```

---

## 🧪 Test 10: Irreversibilidad

### Verificación
Intentar recuperar datos PII después de anonimización:

```sql
-- Intentar recuperar email original
SELECT email FROM wp_survey_participants WHERE id = {participant_id};

-- Intentar recuperar password_hash
SELECT password_hash FROM wp_survey_participants WHERE id = {participant_id};

-- Intentar recuperar nombres
SELECT first_name, last_name FROM wp_survey_participants WHERE id = {participant_id};
```

### Resultado esperado
```
email: anonymous_X@deleted.local (no se puede recuperar el email original)
password_hash: NULL
first_name: NULL
last_name: NULL
```

### Verificación de metadata
```sql
SELECT metadata FROM wp_survey_participants WHERE id = {participant_id};
```

Debería mostrar:
```json
{
  "pii_deleted_at": "2025-02-05 12:00:00"
}
```

---

## ✅ Criterios de Aceptación Finales

Al finalizar todos los tests, verificar:

- [ ] ✅ Tabla `wp_survey_audit_log` creada con schema completo
- [ ] ✅ 8 métodos implementados (0 TODOs restantes)
- [ ] ✅ Anonimizar 1 survey con 100+ participantes en < 2 segundos
- [ ] ✅ Todos los queries usan `$wpdb->prepare()`
- [ ] ✅ Permisos `manage_options` validados en todos los métodos públicos
- [ ] ✅ Audit log registra: action, actor, timestamp, IP, metadata
- [ ] ✅ Magic links invalidados correctamente (used_at ≠ null)
- [ ] ✅ PII completamente borrado (email, password_hash, nombres)
- [ ] ✅ PHPDoc completo en 100% de métodos
- [ ] ✅ Sin errores en `error_log` de PHP

---

## 🐛 Troubleshooting

### Error: "Table doesn't exist"
**Causa:** La tabla no se creó durante la activación.
**Solución:**
```php
EIPSI_Database_Schema_Manager::verify_and_sync_schema();
```

### Error: "Survey already anonymized"
**Causa:** Intento de anonimizar un survey ya anonimizado.
**Solución:** Verificar que `can_anonymize_survey()` retorne true antes de anonimizar.

### Error: "Survey has pending assignments"
**Causa:** Hay assignments con status 'pending' o 'in_progress'.
**Solución:** Completar o cancelar todos los assignments pendientes.

### Error: "insufficient_permissions"
**Causa:** El usuario actual no tiene permisos `manage_options`.
**Solución:**
```php
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to perform this action.');
}
```

---

## 📝 Notas de Implementación

1. **Transacciones de DB:**
   - Se usan transacciones si MySQL >= 5.7.0
   - En caso de error, se hace ROLLBACK automáticamente
   - Logs de transacción en modo debug

2. **Timestamps:**
   - Se usa `current_time('mysql', 1)` para GMT
   - Compatible con timezone de WordPress

3. **JSON Metadata:**
   - Se usa `wp_json_encode()` para serializar
   - Se valida JSON antes de insertar

4. **Logging:**
   - Se usa `error_log()` con formato `[EIPSI Anonymize]`
   - Respetando `EIPSI_LONGITUDINAL_DEBUG`

---

## 🚀 Próximos Pasos (Task 5.2)

Una vez aprobados estos tests, proceder con:
- UI Modal de confirmación para anonimización
- Integración con AJAX handlers
- Notificaciones admin después de anonimización
- Export de audit log en CSV/Excel

---

**Fin de Documento de Testing - PHASE 5.1**
