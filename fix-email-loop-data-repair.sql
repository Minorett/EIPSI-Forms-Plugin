-- ============================================================================
-- EIPSI Forms - Reparación de Datos Históricos del Email Log
-- Fix para el loop de spam de wave_availability emails
-- ============================================================================
-- PROBLEMA: Registros con email_type = '' debido a truncamiento ENUM
-- SOLUCIÓN: Poblar email_type usando wave_index de metadata
-- ============================================================================
-- IMPORTANTE: Ejecutar DESPUÉS de deployar el fix del código
-- ============================================================================

-- ============================================================================
-- PASO 1: VERIFICAR QUÉ SE VA A ACTUALIZAR (SAFE - SOLO LECTURA)
-- ============================================================================
-- Esta query muestra todos los registros que serán actualizados
-- Revisar el output antes de proceder al UPDATE

SELECT 
    id,
    participant_id,
    email_type,
    status,
    sent_at,
    JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.wave_index')) as wave_index,
    JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.wave_id')) as wave_id,
    JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.nudge_stage')) as nudge_stage,
    recipient_email
FROM wp_survey_email_log
WHERE email_type = ''
AND metadata LIKE '%"nudge_stage":0%'
AND metadata LIKE '%"wave_index"%'
ORDER BY sent_at DESC;

-- Esperado: Ver registros con email_type vacío que tienen wave_index en metadata
-- Si el resultado se ve correcto, proceder al Paso 2


-- ============================================================================
-- PASO 2: EJECUTAR EL UPDATE (DESTRUCTIVO - MODIFICA DATOS)
-- ============================================================================
-- Este UPDATE pobla el email_type usando el wave_index de metadata
-- Formato: wave_availability_T1, wave_availability_T2, etc.

UPDATE wp_survey_email_log
SET email_type = CONCAT(
    'wave_availability_T',
    JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.wave_index'))
)
WHERE email_type = ''
AND metadata LIKE '%"nudge_stage":0%'
AND metadata LIKE '%"wave_index"%'
AND JSON_EXTRACT(metadata, '$.wave_index') IS NOT NULL;

-- Resultado esperado: X rows affected (donde X es el número del Paso 1)


-- ============================================================================
-- PASO 3: VERIFICAR RESULTADO (SAFE - SOLO LECTURA)
-- ============================================================================
-- Confirmar que los email_type fueron poblados correctamente

SELECT 
    email_type, 
    COUNT(*) as count,
    MIN(sent_at) as primer_registro,
    MAX(sent_at) as ultimo_registro
FROM wp_survey_email_log
WHERE email_type LIKE 'wave_availability%'
GROUP BY email_type
ORDER BY count DESC;

-- Esperado: Ver distribución de wave_availability_T1, T2, T3, etc.


-- ============================================================================
-- PASO 4: CONFIRMAR QUE NO QUEDAN REGISTROS VACÍOS DE NUDGE 0
-- ============================================================================
-- Esta query debe retornar 0 si la reparación fue exitosa

SELECT COUNT(*) as registros_vacios_restantes
FROM wp_survey_email_log
WHERE email_type = ''
AND metadata LIKE '%"nudge_stage":0%';

-- Esperado: 0 registros


-- ============================================================================
-- PASO 5 (OPCIONAL): AUDITORÍA DE PARTICIPANTES AFECTADOS
-- ============================================================================
-- Ver qué participantes tenían registros con email_type vacío

SELECT 
    recipient_email,
    COUNT(*) as emails_reparados,
    MIN(sent_at) as primer_email,
    MAX(sent_at) as ultimo_email
FROM wp_survey_email_log
WHERE email_type LIKE 'wave_availability_T%'
AND sent_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY recipient_email
HAVING emails_reparados > 5
ORDER BY emails_reparados DESC;

-- Esperado: Lista de participantes que recibieron múltiples emails por el bug


-- ============================================================================
-- PASO 6 (OPCIONAL): LIMPIAR DUPLICADOS EXCESIVOS
-- ============================================================================
-- Si un participante tiene más de 10 registros del mismo wave_id,
-- mantener solo los primeros 3 y marcar el resto como 'duplicate'
-- ADVERTENCIA: Esto modifica el status de registros históricos

-- Primero, ver cuántos duplicados hay:
SELECT 
    participant_id,
    JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.wave_id')) as wave_id,
    COUNT(*) as total_emails
FROM wp_survey_email_log
WHERE email_type LIKE 'wave_availability_T%'
GROUP BY participant_id, JSON_EXTRACT(metadata, '$.wave_id')
HAVING total_emails > 10
ORDER BY total_emails DESC;

-- Si quieres marcar duplicados excesivos (OPCIONAL - DESTRUCTIVO):
/*
UPDATE wp_survey_email_log e1
SET status = 'duplicate'
WHERE email_type LIKE 'wave_availability_T%'
AND id NOT IN (
    SELECT id FROM (
        SELECT id
        FROM wp_survey_email_log e2
        WHERE e2.participant_id = e1.participant_id
        AND JSON_EXTRACT(e2.metadata, '$.wave_id') = JSON_EXTRACT(e1.metadata, '$.wave_id')
        ORDER BY sent_at ASC
        LIMIT 3
    ) as keeper
);
*/


-- ============================================================================
-- ROLLBACK (SOLO SI ALGO SALIÓ MAL)
-- ============================================================================
-- Si el UPDATE del Paso 2 causó problemas, puedes revertir con:
/*
UPDATE wp_survey_email_log
SET email_type = ''
WHERE email_type LIKE 'wave_availability_T%'
AND sent_at < 'YYYY-MM-DD HH:MM:SS'; -- Reemplazar con timestamp antes del UPDATE
*/


-- ============================================================================
-- NOTAS IMPORTANTES
-- ============================================================================
-- 1. Ejecutar en horario de bajo tráfico si es posible
-- 2. Hacer backup de wp_survey_email_log antes del Paso 2
-- 3. El Paso 2 es idempotente: se puede ejecutar múltiples veces sin problemas
-- 4. Los Pasos 1, 3, 4, 5, 6 son SAFE (solo lectura)
-- 5. El Paso 2 es DESTRUCTIVO pero reversible con el ROLLBACK
-- 6. Después del fix, monitorear logs durante 10 minutos para confirmar
--    que el loop se detuvo
