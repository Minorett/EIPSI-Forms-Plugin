-- ============================================================================
-- EIPSI DIAGNOSTIC QUERIES - Email Loop Investigation
-- Ejecutar en producción para confirmar hipótesis
-- ============================================================================

-- QUERY 1: Estado actual de la columna email_type
-- Esperado: VARCHAR(100) si la migración corrió, ENUM si no
-- ============================================================================
SHOW COLUMNS FROM wp_survey_email_log LIKE 'email_type';


-- QUERY 2: Registros de los participantes afectados
-- Buscar si tienen email_type vacío y si metadata tiene wave_id
-- ============================================================================
SELECT 
    id, 
    email_type, 
    status,
    sent_at,
    metadata,
    recipient_email
FROM wp_survey_email_log 
WHERE recipient_email IN ('svekry@docbao7.com', 'arthurqueiroz79@wangdandan-w.cc')
ORDER BY id ASC 
LIMIT 20;


-- QUERY 3: Verificar si existen transients de deduplicación
-- Si están vacíos, el contador de reintentos se resetea
-- ============================================================================
SELECT 
    option_name, 
    option_value 
FROM wp_options 
WHERE option_name LIKE '%wave_email_retries%';


-- QUERY 4: Contar registros con email_type vacío vs poblado
-- Para dimensionar el problema
-- ============================================================================
SELECT 
    CASE 
        WHEN email_type = '' THEN 'VACÍO'
        WHEN email_type IS NULL THEN 'NULL'
        ELSE 'POBLADO'
    END AS tipo_estado,
    COUNT(*) as cantidad,
    MIN(sent_at) as primer_registro,
    MAX(sent_at) as ultimo_registro
FROM wp_survey_email_log
GROUP BY tipo_estado;


-- QUERY 5: Registros específicos de wave_availability con email_type vacío
-- Para confirmar el patrón exacto del problema
-- ============================================================================
SELECT 
    id,
    participant_id,
    email_type,
    status,
    sent_at,
    JSON_EXTRACT(metadata, '$.wave_id') as wave_id,
    JSON_EXTRACT(metadata, '$.nudge_stage') as nudge_stage,
    metadata
FROM wp_survey_email_log
WHERE email_type = ''
  AND metadata LIKE '%wave_id%'
ORDER BY sent_at DESC
LIMIT 10;


-- QUERY 6: Últimos 50 emails enviados a los participantes afectados
-- Para ver el patrón temporal del loop
-- ============================================================================
SELECT 
    id,
    email_type,
    status,
    sent_at,
    JSON_EXTRACT(metadata, '$.wave_id') as wave_id,
    JSON_EXTRACT(metadata, '$.nudge_stage') as nudge_stage
FROM wp_survey_email_log
WHERE recipient_email IN ('svekry@docbao7.com', 'arthurqueiroz79@wangdandan-w.cc')
ORDER BY sent_at DESC
LIMIT 50;
