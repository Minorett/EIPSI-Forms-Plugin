<?php
/**
 * Nudge Cache Service
 * 
 * Sistema de cache para cálculos de nudges usando transients de WordPress.
 * Evita recalcular timestamps inmutables en cada ejecución del cron.
 * 
 * @package EIPSI_Forms
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cache para cálculos de nudges
 */
class EIPSI_Nudge_Cache {
    
    /**
     * Prefijo para las claves de cache
     */
    const CACHE_PREFIX = 'eipsi_nudge_';
    
    /**
     * TTL por defecto: 24 horas (cálculos inmutables)
     */
    const DEFAULT_TTL = DAY_IN_SECONDS;
    
    /**
     * TTL corto: 5 minutos (datos que pueden cambiar)
     */
    const SHORT_TTL = 300;
    
    /**
     * Obtener timestamp de trigger para un nudge (con cache)
     * 
     * @param int $assignment_id ID de la asignación
     * @param int $stage Número de nudge (0-4)
     * @param object|null $assignment Objeto de asignación (opcional, para evitar query)
     * @param object|null $wave Objeto de wave (opcional)
     * @return int|false Timestamp o false si no calculable
     */
    public static function get_trigger_timestamp($assignment_id, $stage, $assignment = null, $wave = null) {
        $cache_key = self::get_cache_key($assignment_id, $stage, 'trigger_ts');
        
        // Intentar obtener de cache
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    '[EIPSI NudgeCache] CACHE HIT: trigger_ts for assignment %d, stage %d = %s',
                    $assignment_id,
                    $stage,
                    date('Y-m-d H:i:s', $cached)
                ));
            }
            return intval($cached);
        }
        
        // Calcular
        $timestamp = self::calculate_trigger_timestamp($assignment_id, $stage, $assignment, $wave);
        
        if ($timestamp !== false) {
            // Guardar en cache (TTL largo porque el cálculo nunca cambia)
            set_transient($cache_key, $timestamp, self::DEFAULT_TTL);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    '[EIPSI NudgeCache] CACHE MISS: Calculated and cached trigger_ts for assignment %d, stage %d = %s',
                    $assignment_id,
                    $stage,
                    date('Y-m-d H:i:s', $timestamp)
                ));
            }
        }
        
        return $timestamp;
    }
    
    /**
     * Verificar si un nudge ya fue enviado (con cache)
     * 
     * @param int $assignment_id ID de la asignación
     * @param int $stage Número de nudge
     * @return bool|null true=enviado, false=no enviado, null=desconocido
     */
    public static function is_nudge_sent($assignment_id, $stage) {
        $cache_key = self::get_cache_key($assignment_id, $stage, 'sent');
        
        $cached = get_transient($cache_key);
        
        if ($cached === '1') {
            return true;
        } elseif ($cached === '0') {
            return false;
        }
        
        // No hay cache, devolver null para indicar que hay que consultar BD
        return null;
    }
    
    /**
     * Marcar nudge como enviado en cache
     * 
     * @param int $assignment_id ID de la asignación
     * @param int $stage Número de nudge
     */
    public static function mark_nudge_sent($assignment_id, $stage) {
        $cache_key = self::get_cache_key($assignment_id, $stage, 'sent');
        set_transient($cache_key, '1', self::DEFAULT_TTL);
        
        // Invalidar cache de "debería enviarse" si existe
        $should_send_key = self::get_cache_key($assignment_id, $stage, 'should_send');
        delete_transient($should_send_key);
    }
    
    /**
     * Cachear resultado de should_send_nudge
     * 
     * @param int $assignment_id ID de la asignación
     * @param int $stage Número de nudge
     * @param bool $result Resultado
     * @param int $ttl Tiempo de vida (default: corto porque puede cambiar)
     */
    public static function cache_should_send($assignment_id, $stage, $result, $ttl = self::SHORT_TTL) {
        $cache_key = self::get_cache_key($assignment_id, $stage, 'should_send');
        set_transient($cache_key, $result ? '1' : '0', $ttl);
    }
    
    /**
     * Obtener cache de should_send_nudge
     * 
     * @param int $assignment_id ID de la asignación
     * @param int $stage Número de nudge
     * @return bool|null true/false o null si no hay cache
     */
    public static function get_cached_should_send($assignment_id, $stage) {
        $cache_key = self::get_cache_key($assignment_id, $stage, 'should_send');
        $cached = get_transient($cache_key);
        
        if ($cached === '1') return true;
        if ($cached === '0') return false;
        return null;
    }
    
    /**
     * Invalidar todo el cache para una asignación
     * Útil cuando el participante completa una toma o cambia estado
     * 
     * @param int $assignment_id ID de la asignación
     */
    public static function invalidate_assignment_cache($assignment_id) {
        // WordPress no permite listar transients por patrón, así que
        // usamos un enfoque de "versión de cache"
        $version_key = self::CACHE_PREFIX . "assignment_{$assignment_id}_version";
        $current_version = get_transient($version_key);
        $new_version = $current_version ? $current_version + 1 : 1;
        
        set_transient($version_key, $new_version, self::DEFAULT_TTL);
        
        // También invalidar cálculos específicos
        for ($stage = 0; $stage <= 4; $stage++) {
            delete_transient(self::get_cache_key($assignment_id, $stage, 'trigger_ts'));
            delete_transient(self::get_cache_key($assignment_id, $stage, 'sent'));
            delete_transient(self::get_cache_key($assignment_id, $stage, 'should_send'));
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[EIPSI NudgeCache] Invalidated all cache for assignment %d (version: %d)',
                $assignment_id,
                $new_version
            ));
        }
    }
    
    /**
     * Calcular timestamp de trigger (la lógica pesada)
     * 
     * @param int $assignment_id ID de la asignación
     * @param int $stage Número de nudge
     * @param object|null $assignment Objeto de asignación (opcional)
     * @param object|null $wave Objeto de wave (opcional)
     * @return int|false
     */
    private static function calculate_trigger_timestamp($assignment_id, $stage, $assignment = null, $wave = null) {
        global $wpdb;
        
        // Si no tenemos los objetos, cargarlos
        if (!$assignment) {
            $assignment = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}survey_assignments WHERE id = %d",
                $assignment_id
            ));
        }
        
        if (!$assignment) {
            return false;
        }
        
        // Para nudge 0, es el available_at
        if ($stage === 0) {
            return !empty($assignment->available_at) 
                ? strtotime($assignment->available_at)
                : false;
        }
        
        // Para nudges 1-4, necesitamos la configuración de la wave
        if (!$wave) {
            $wave = $wpdb->get_row($wpdb->prepare(
                "SELECT nudge_config FROM {$wpdb->prefix}survey_waves WHERE id = %d",
                $assignment->wave_id
            ));
        }
        
        if (!$wave || empty($wave->nudge_config)) {
            return false;
        }
        
        $nudge_config = json_decode($wave->nudge_config, true);
        $nudge_key = "nudge_{$stage}";
        
        if (!isset($nudge_config[$nudge_key])) {
            return false;
        }
        
        $config = $nudge_config[$nudge_key];
        
        // Si no está habilitado, no hay trigger
        if (empty($config['enabled'])) {
            return false;
        }
        
        $value = isset($config['value']) ? intval($config['value']) : ($stage * 24);
        $unit = isset($config['unit']) ? $config['unit'] : 'hours';
        
        // Calcular delay en segundos
        $delay_seconds = self::convert_to_seconds($value, $unit);
        
        // El trigger es available_at + delay
        $available_at = !empty($assignment->available_at) 
            ? strtotime($assignment->available_at)
            : current_time('timestamp');
        
        return $available_at + $delay_seconds;
    }
    
    /**
     * Generar clave de cache
     */
    private static function get_cache_key($assignment_id, $stage, $type) {
        // Incluir versión de cache en la clave para invalidación eficiente
        $version_key = self::CACHE_PREFIX . "assignment_{$assignment_id}_version";
        $version = get_transient($version_key);
        $version_suffix = $version ? "_v{$version}" : "_v0";
        
        return self::CACHE_PREFIX . "a{$assignment_id}_s{$stage}_{$type}{$version_suffix}";
    }
    
    /**
     * Convertir unidad a segundos
     */
    private static function convert_to_seconds($value, $unit) {
        switch ($unit) {
            case 'minutes':
                return $value * 60;
            case 'hours':
                return $value * 3600;
            case 'days':
                return $value * 86400;
            default:
                return $value * 3600;
        }
    }
    
    /**
     * Obtener estadísticas de cache (para debugging)
     */
    public static function get_stats() {
        global $wpdb;
        
        // WordPress no expone estadísticas de transients fácilmente
        // Esta función puede extenderse con un plugin de cache externo
        
        return array(
            'hits' => get_transient(self::CACHE_PREFIX . 'stats_hits') ?: 0,
            'misses' => get_transient(self::CACHE_PREFIX . 'stats_misses') ?: 0,
            'note' => 'Stats require external cache implementation for accurate tracking'
        );
    }
    
    /**
     * Limpiar todo el cache de nudges (mantenimiento)
     */
    public static function clear_all_cache() {
        global $wpdb;
        
        // Eliminar todos los transients con nuestro prefijo
        // Nota: esto puede ser lento en sitios grandes
        $option_names = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . '%'
            )
        );
        
        foreach ($option_names as $option_name) {
            $transient_name = str_replace('_transient_', '', $option_name);
            delete_transient($transient_name);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[EIPSI NudgeCache] Cleared %d cached entries', count($option_names)));
        }
        
        return count($option_names);
    }
}
