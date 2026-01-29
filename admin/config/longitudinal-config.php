<?php
/**
 * Configuración del sistema longitudinal
 * 
 * Este archivo define las constantes y configuraciones para el sistema
 * de estudios longitudinales de EIPSI Forms.
 *
 * @package EIPSI_Forms
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// =================================================================
// ESTRATEGIA DE IDENTIFICACIÓN DE PARTICIPANTES
// =================================================================

/**
 * Estrategia de identificación de participantes
 * 
 * Opciones:
 * - 'email': Default para login longitudinal con email+password
 * - 'fingerprint': Legacy para estudios sin login (rastreo pasivo)
 * 
 * En estudios longitudinales con login, usamos 'email' para permitir
 * que los participantes accedan desde diferentes dispositivos.
 */
define('EIPSI_PARTICIPANT_ID_STRATEGY', 'email');

// =================================================================
// VERSIONADO DE SCHEMA
// =================================================================

/**
 * Versión del schema longitudinal
 *
 * Se usa para migraciones de base de datos. Cambiar este número
 * cuando se modifiquen las tablas wp_survey_* para ejecutar migraciones.
 *
 * @since 1.4.0
 */
define('EIPSI_LONGITUDINAL_DB_VERSION', '1.1.0');

// =================================================================
// CONFIGURACIÓN DE SESIONES
// =================================================================

/**
 * Tiempo de vida de la sesión del plugin (en horas)
 * 
 * Default: 168 horas = 7 días
 * La sesión se almacena en una cookie HTTP-only y en wp_survey_sessions.
 */
define('EIPSI_SESSION_TTL_HOURS', 168);

/**
 * Nombre de la cookie de sesión del plugin
 * 
 * Cookie HTTP-only para seguridad contra XSS.
 * No confundir con las cookies de WordPress.
 */
define('EIPSI_SESSION_COOKIE_NAME', 'eipsi_participant_session');

/**
 * Ruta de la cookie de sesión
 * 
 * '/' para que esté disponible en todo el sitio.
 */
define('EIPSI_SESSION_COOKIE_PATH', '/');

/**
 * Dominio de la cookie de sesión
 * 
 * false para usar el dominio actual.
 */
define('EIPSI_SESSION_COOKIE_DOMAIN', false);

/**
 * Flag de seguridad para cookie de sesión
 * 
 * true para enviar solo sobre HTTPS (si el sitio tiene SSL).
 * Si el sitio no tiene SSL, debe ser false.
 */
define('EIPSI_SESSION_COOKIE_SECURE', is_ssl());

/**
 * Flag de SameSite para cookie de sesión
 * 
 * 'Lax' o 'Strict'. 'Strict' es más seguro pero puede causar problemas
 * con enlaces externos. 'Lax' permite navegación desde sitios externos.
 */
define('EIPSI_SESSION_COOKIE_SAMESITE', 'Lax');

// =================================================================
// CONFIGURACIÓN DE MAGIC LINKS
// =================================================================

/**
 * Expiración de magic links (en horas)
 * 
 * Default: 48 horas = 2 días
 * Los magic links permiten acceso directo sin login manual.
 */
define('EIPSI_MAGIC_LINK_EXPIRY_HOURS', 48);

/**
 * Usos máximos de un magic link
 * 
 * Default: 1 uso
 * Por seguridad, un magic link solo puede usarse una vez.
 */
define('EIPSI_MAGIC_LINK_MAX_USES', 1);

/**
 * Longitud del token de magic link
 * 
 * Default: 64 caracteres
 * Generado con wp_generate_password(64, true, true).
 */
define('EIPSI_MAGIC_LINK_TOKEN_LENGTH', 64);

/**
 * Prefijo del parámetro de magic link en URL
 * 
 * Ejemplo: site_url() . "?eipsi_magic={$token}"
 */
define('EIPSI_MAGIC_LINK_URL_PARAM', 'eipsi_magic');

// =================================================================
// CONFIGURACIÓN DE WAVES
// =================================================================

/**
 * Índice mínimo de wave
 * 
 * Las waves se numeran desde 1 en adelante (1 = baseline, 2 = follow-up 1, etc.)
 */
define('EIPSI_WAVE_INDEX_MIN', 1);

/**
 * Estado por defecto de una wave asignada
 * 
 * Opciones: 'pending', 'in_progress', 'submitted'
 */
define('EIPSI_WAVE_DEFAULT_STATUS', 'pending');

// =================================================================
// CONFIGURACIÓN DE EMAILS
// =================================================================

/**
 * Rate limit para envío de emails por cron (por ejecución)
 * 
 * Default: 100 emails máximo por ejecución del cron
 * Evita sobrecargar el servidor de correo.
 */
define('EIPSI_CRON_EMAIL_RATE_LIMIT', 100);

/**
 * Intervalo entre recordatorios de wave (en horas)
 * 
 * Default: 24 horas = 1 día
 * No enviar más de un recordatorio por wave cada 24 horas.
 */
define('EIPSI_WAVE_REMINDER_INTERVAL_HOURS', 24);

/**
 * Reminders máximos por wave
 * 
 * Default: 3 recordatorios máximo por wave pendiente
 */
define('EIPSI_WAVE_MAX_REMINDERS', 3);

// =================================================================
// CONFIGURACIÓN DE ANONIMIZACIÓN
// =================================================================

/**
 * Prefijo para emails anonimizados
 * 
 * Ejemplo: 'anonymous_123@deleted.local'
 */
define('EIPSI_ANONYMOUS_EMAIL_PREFIX', 'anonymous_');

/**
 * Dominio para emails anonimizados
 * 
 * Ejemplo: 'deleted.local' (dominio inválido para evitar envíos)
 */
define('EIPSI_ANONYMOUS_EMAIL_DOMAIN', 'deleted.local');

/**
 * Acciones que requieren auditoría obligatoria
 * 
 * Estas acciones SIEMPRE se registran en wp_survey_audit_log.
 */
define('EIPSI_AUDIT_REQUIRED_ACTIONS', serialize(array(
    'anonymize_survey',
    'anonymize_participant',
    'invalidate_magic_links',
    'delete_participant',
    'manual_override_wave_status',
)));

// =================================================================
// CONFIGURACIÓN DE TOLERANCIA A FALLAS
// =================================================================

/**
 * Intentos máximos de login
 * 
 * Default: 5 intentos antes de bloquear temporalmente.
 */
define('EIPSI_LOGIN_MAX_ATTEMPTS', 5);

/**
 * Tiempo de bloqueo tras fallas de login (en minutos)
 * 
 * Default: 15 minutos
 */
define('EIPSI_LOGIN_LOCKOUT_TIME_MINUTES', 15);

/**
 * Timeout de sesión de inactividad (en horas)
 * 
 * Default: 2 horas de inactividad antes de requerir re-login.
 * Esto es diferente a EIPSI_SESSION_TTL_HOURS que es el máximo absoluto.
 */
define('EIPSI_SESSION_INACTIVITY_TIMEOUT_HOURS', 2);

// =================================================================
// CONFIGURACIÓN DE DESARROLLO/DEBUG
// =================================================================

/**
 * Modo debug para longitudinal
 * 
 * true: Muestra logs detallados en error_log()
 * false: Solo logs de errores críticos
 */
define('EIPSI_LONGITUDINAL_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

/**
 * Habilitar logs de rendimiento
 * 
 * true: Registra tiempos de ejecución de operaciones críticas
 * false: Sin logs de rendimiento
 */
define('EIPSI_PERFORMANCE_LOGGING', false);

// =================================================================
// CONFIGURACIÓN DE MIGRACIÓN
// =================================================================

/**
 * Versión mínima requerida para migrar desde v1.3.x
 * 
 * Si la versión actual es menor, se requerirá actualización manual.
 */
define('EIPSI_LONGITUDINAL_MIN_VERSION', '1.3.20');

/**
 * Flag para habilitar migración automática
 * 
 * true: Migración automática al activar el plugin
 * false: Requiere intervención manual (para producción)
 */
define('EIPSI_AUTO_MIGRATE_ENABLED', true);

// =================================================================
// CONFIGURACIÓN DE PRIVACIDAD
// =================================================================

/**
 * Retención de datos anonimizados (en días)
 * 
 * Default: 365 días = 1 año
 * Después de anonimizar, ¿cuánto tiempo mantener los datos clínicos?
 * null = retención indefinida.
 */
define('EIPSI_ANONYMIZED_DATA_RETENTION_DAYS', 365);

/**
 * Flag para permitir borrado completo de datos anonimizados
 * 
 * true: Admin puede borrar completamente datos anonimizados
 * false: Solo se anonimizan, no se borran (conservación histórica)
 */
define('EIPSI_ALLOW_DELETE_ANONYMIZED_DATA', false);

// =================================================================
// FIN DE CONFIGURACIÓN
// =================================================================

/**
 * Log de configuración cargada (solo en debug)
 */
if (EIPSI_LONGITUDINAL_DEBUG) {
    error_log(sprintf(
        '[EIPSI Forms] Longitudinal config loaded (v%s) - Session: %dh, Magic Link: %dh',
        EIPSI_LONGITUDINAL_DB_VERSION,
        EIPSI_SESSION_TTL_HOURS,
        EIPSI_MAGIC_LINK_EXPIRY_HOURS
    ));
}
