-- ============================================================================
-- EIPSI Forms - Tests de Verificación Post-Implementación
-- Verificar que el fix del deduplicador funciona correctamente
-- ============================================================================
-- IMPORTANTE: Ejecutar DESPUÉS de deployar el fix del código
-- ============================================================================

-- ============================================================================
-- TEST 1: DEDUPLICACIÓN CON TIPO NUEVO (wave_availability_T3)
-- ============================================================================
-- Objetivo: Verificar que el deduplicador detecta registros con tipo nuevo

-- 1.1 - Insertar registro de prueba
INSERT INTO wp_survey_email_log 
(participant_id, email_type, status, metadata, recipient_email, sent_at, created_at)
VALUES (
    999999,
    'wave_availability_T3',
    'sent',
    '{"wave_id": 999, "wave_index": 3, "nudge_stage": 0}',
    'test@example.com',
    NOW(),
    NOW()
);

-- 1.2 - Verificar que el registro fue insertado
SELECT 
    id, 
    participant_id, 
    email_type, 
    status,
    JSON_EXTRACT(metadata, '$.wave_id') as wave_id,
    JSON_EXTRACT(metadata, '$.nudge_stage') as nudge_stage
FROM wp_survey_email_log
WHERE participant_id = 999999;

-- Esperado: 1 registro con email_type = 'wave_availability_T3'

-- 1.3 - MANUAL: Llamar a was_nudge_zero_already_sent(999999, 999) desde PHP
-- Debe retornar TRUE
-- Verificar en error_log que aparece el mensaje de deduplicación

-- 1.4 - Limpiar después del test
DELETE FROM wp_survey_email_log WHERE participant_id = 999999;

-- 1.5 - Confirmar limpieza
SELECT COUNT(*) as registros_restantes
FROM wp_survey_email_log
WHERE participant_id = 999999;

-- Esperado: 0 registros


-- ============================================================================
-- TEST 2: DEDUPLICACIÓN CON EMAIL_TYPE VACÍO
-- ============================================================================
-- Objetivo: Verificar que el deduplicador detecta registros históricos vacíos

-- 2.1 - Insertar registro de prueba con email_type vacío
INSERT INTO wp_survey_email_log 
(participant_id, email_type, status, metadata, recipient_email, sent_at, created_at)
VALUES (
    999999,
    '',
    'sent',
    '{"wave_id": 888, "wave_index": 2, "nudge_stage": 0}',
    'test@example.com',
    NOW(),
    NOW()
);

-- 2.2 - Verificar que el registro fue insertado
SELECT 
    id, 
    participant_id, 
    email_type, 
    status,
    JSON_EXTRACT(metadata, '$.wave_id') as wave_id,
    JSON_EXTRACT(metadata, '$.nudge_stage') as nudge_stage
FROM wp_survey_email_log
WHERE participant_id = 999999;

-- Esperado: 1 registro con email_type = '' (vacío)

-- 2.3 - MANUAL: Llamar a was_nudge_zero_already_sent(999999, 888) desde PHP
-- Debe retornar TRUE
-- Verificar en error_log que aparece el mensaje:
-- "[EIPSI WaveEmail] Deduplicación por fallback metadata: participant=999999 wave_id=888 log_id=X"

-- 2.4 - MANUAL: Llamar a was_nudge_zero_already_sent(999999, 777) desde PHP
-- Debe retornar FALSE (wave_id diferente)

-- 2.5 - Limpiar después del test
DELETE FROM wp_survey_email_log WHERE participant_id = 999999;

-- 2.6 - Confirmar limpieza
SELECT COUNT(*) as registros_restantes
FROM wp_survey_email_log
WHERE participant_id = 999999;

-- Esperado: 0 registros


-- ============================================================================
-- TEST 3: DEDUPLICACIÓN CON TIPO LEGACY
-- ============================================================================
-- Objetivo: Verificar que el deduplicador detecta registros legacy

-- 3.1 - Insertar registro de prueba con tipo legacy
INSERT INTO wp_survey_email_log 
(participant_id, email_type, status, metadata, recipient_email, sent_at, created_at)
VALUES (
    999999,
    'reminder',
    'sent',
    '{"wave_id": 777, "wave_index": 1, "nudge_stage": 0}',
    'test@example.com',
    NOW(),
    NOW()
);

-- 3.2 - Verificar que el registro fue insertado
SELECT 
    id, 
    participant_id, 
    email_type, 
    status,
    JSON_EXTRACT(metadata, '$.wave_id') as wave_id
FROM wp_survey_email_log
WHERE participant_id = 999999;

-- Esperado: 1 registro con email_type = 'reminder'

-- 3.3 - MANUAL: Llamar a was_nudge_zero_already_sent(999999, 777) desde PHP
-- Debe retornar TRUE

-- 3.4 - Limpiar después del test
DELETE FROM wp_survey_email_log WHERE participant_id = 999999;


-- ============================================================================
-- TEST 4: NO DEDUPLICAR WAVES DIFERENTES
-- ============================================================================
-- Objetivo: Verificar que el deduplicador NO bloquea emails de waves distintos

-- 4.1 - Insertar registro de wave_id=100
INSERT INTO wp_survey_email_log 
(participant_id, email_type, status, metadata, recipient_email, sent_at, created_at)
VALUES (
    999999,
    'wave_availability_T1',
    'sent',
    '{"wave_id": 100, "wave_index": 1, "nudge_stage": 0}',
    'test@example.com',
    NOW(),
    NOW()
);

-- 4.2 - MANUAL: Llamar a was_nudge_zero_already_sent(999999, 100) desde PHP
-- Debe retornar TRUE (mismo wave)

-- 4.3 - MANUAL: Llamar a was_nudge_zero_already_sent(999999, 200) desde PHP
-- Debe retornar FALSE (wave diferente)

-- 4.4 - Limpiar después del test
DELETE FROM wp_survey_email_log WHERE participant_id = 999999;


-- ============================================================================
-- TEST 5: VERIFICAR QUE EL LOOP SE DETUVO EN PRODUCCIÓN
-- ============================================================================
-- Objetivo: Confirmar que los participantes afectados dejaron de recibir emails

-- 5.1 - Contar emails enviados en los últimos 10 minutos a participantes afectados
SELECT 
    recipient_email,
    COUNT(*) as emails_ultimos_10min,
    MAX(sent_at) as ultimo_email
FROM wp_survey_email_log
WHERE recipient_email IN ('svekry@docbao7.com', 'arthurqueiroz79@wangdandan-w.cc')
AND sent_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
GROUP BY recipient_email;

-- Esperado ANTES del fix: Múltiples emails (5-10)
-- Esperado DESPUÉS del fix: 0 o 1 email máximo

-- 5.2 - Ver el último email enviado a cada participante afectado
SELECT 
    recipient_email,
    email_type,
    status,
    sent_at,
    JSON_EXTRACT(metadata, '$.wave_id') as wave_id,
    JSON_EXTRACT(metadata, '$.nudge_stage') as nudge_stage
FROM wp_survey_email_log
WHERE recipient_email IN ('svekry@docbao7.com', 'arthurqueiroz79@wangdandan-w.cc')
ORDER BY sent_at DESC
LIMIT 10;

-- Esperado: No debe haber emails nuevos después del deployment del fix


-- ============================================================================
-- TEST 6: VERIFICAR LOGS DE ERROR
-- ============================================================================
-- Objetivo: Confirmar que los logs muestran deduplicación correcta

-- 6.1 - MANUAL: Revisar error_log de WordPress
-- Buscar líneas con:
-- "[EIPSI WaveEmail] Deduplicación por fallback metadata"
-- "[EIPSI WaveEmail] Nudge 0 ya enviado"

-- 6.2 - MANUAL: Verificar que NO aparecen errores de:
-- - JSON malformado
-- - Queries SQL fallidas
-- - Excepciones no capturadas


-- ============================================================================
-- TEST 7: VERIFICAR METADATA INTEGRITY
-- ============================================================================
-- Objetivo: Confirmar que todos los registros tienen metadata válida

-- 7.1 - Buscar registros con metadata NULL o vacía
SELECT 
    id,
    participant_id,
    email_type,
    status,
    sent_at,
    metadata
FROM wp_survey_email_log
WHERE email_type LIKE 'wave_availability%'
AND (metadata IS NULL OR metadata = '' OR metadata = '{}')
ORDER BY sent_at DESC
LIMIT 10;

-- Esperado: 0 registros (o muy pocos registros legacy)

-- 7.2 - Buscar registros con JSON inválido
SELECT 
    id,
    participant_id,
    email_type,
    metadata
FROM wp_survey_email_log
WHERE email_type LIKE 'wave_availability%'
AND JSON_VALID(metadata) = 0
LIMIT 10;

-- Esperado: 0 registros


-- ============================================================================
-- RESUMEN DE VERIFICACIÓN
-- ============================================================================
-- Checklist post-deployment:
-- 
-- ✅ Test 1: Deduplicación con tipo nuevo funciona
-- ✅ Test 2: Deduplicación con email_type vacío funciona
-- ✅ Test 3: Deduplicación con tipo legacy funciona
-- ✅ Test 4: No hay falsos positivos entre waves diferentes
-- ✅ Test 5: El loop se detuvo en producción
-- ✅ Test 6: Los logs muestran deduplicación correcta
-- ✅ Test 7: Metadata integrity está OK
-- 
-- Si todos los tests pasan, el fix es exitoso.
-- Si algún test falla, revisar error_log y reportar el problema.
