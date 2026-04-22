# Roadmap Save & Continue - Mejoras Post-Audit v1.3.15

**Fecha:** Abril 2026  
**Estado:** Listo para implementación  
**Puntaje Audit:** 7.2/10  
**Objetivo:** Llegar a 9.0/10

---

## Estado Actual (Pre-Implementación)

**Archivos involucrados:**
- `src/frontend/eipsi-save-continue.js` - Frontend Save & Continue
- `admin/partial-responses.php` - Backend partial responses
- `admin/ajax-handlers.php` (líneas 2837-3006) - AJAX handlers

**Estado de cada componente:**
- ✅ Rate limiting implementado
- ✅ Debounce de 5s implementado
- ✅ IndexedDB + servidor en paralelo
- ✅ Popup de recuperación funciona
- ❌ Race condition en savePartial (pendiente)
- ❌ Expiración de sesiones (pendiente)
- ❌ Índices faltantes en DB (pendiente)
- ❌ Dirty tracking (pendiente)
- ❌ Circuit breaker (pendiente)
- ❌ Indicador visual (pendiente)

---

## FASE 1 - Prioridad ALTA (Semana 1)

### Fix 1 — Queue Pattern para savePartial (Race Condition CRÍTICA)

**Archivo:** `src/frontend/eipsi-save-continue.js`

**Cambio:** Reemplazar `pendingSync` boolean por queue-based approach

**Nueva lógica:**
```javascript
// En constructor:
this.saveQueue = [];
this.processingQueue = false;

// Reemplazar llamadas a savePartial() por queueSave()
async queueSave(trigger = 'manual') {
    if (this.completed) return;
    
    // Descartar saves duplicados en cola — solo mantener el último
    this.saveQueue = this.saveQueue.filter(s => s.trigger !== trigger);
    this.saveQueue.push({ trigger, timestamp: Date.now() });
    
    if (!this.processingQueue) {
        await this.processSaveQueue();
    }
}

async processSaveQueue() {
    this.processingQueue = true;
    while (this.saveQueue.length > 0) {
        const save = this.saveQueue.shift();
        await this.executeSave(save.trigger);
    }
    this.processingQueue = false;
}

async executeSave(trigger) {
    // Lógica actual de savePartial() migrada aquí
}
```

**Testing:** Navegar rápidamente entre páginas mientras escribe → ¿se pierden datos?

---

### Fix 2 — Expiración Automática de Sesiones Parciales

**Archivo:** `eipsi-forms.php` (plugin principal)

**Cambio:** Registrar cron job diario para cleanup

**Código:**
```php
if (!wp_next_scheduled('eipsi_cleanup_partial_responses')) {
    wp_schedule_event(time(), 'daily', 'eipsi_cleanup_partial_responses');
}
add_action('eipsi_cleanup_partial_responses', 'eipsi_run_partial_cleanup');

function eipsi_run_partial_cleanup() {
    global $wpdb;
    $table = $wpdb->prefix . 'eipsi_partial_responses';
    
    // Eliminar incompletos de más de 7 días
    $deleted_incomplete = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$table} WHERE updated_at < %s AND completed = 0",
        date('Y-m-d H:i:s', strtotime('-7 days'))
    ));
    
    // Eliminar completados de más de 30 días
    $deleted_complete = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$table} WHERE updated_at < %s AND completed = 1",
        date('Y-m-d H:i:s', strtotime('-30 days'))
    ));
    
    // Eliminar sesiones huérfanas — mismo participante/form con más de 3 sesiones
    $wpdb->query("
        DELETE p1 FROM {$table} p1
        INNER JOIN (
            SELECT form_id, participant_id, session_id,
                   ROW_NUMBER() OVER (
                       PARTITION BY form_id, participant_id
                       ORDER BY updated_at DESC
                   ) as rn
            FROM {$table}
            WHERE completed = 0
        ) p2 ON p1.form_id = p2.form_id
             AND p1.participant_id = p2.participant_id
             AND p1.session_id = p2.session_id
        WHERE p2.rn > 3
    ");
    
    error_log("[EIPSI Save&Continue] Cleanup: {$deleted_incomplete} incompletos y {$deleted_complete} completados eliminados.");
}
```

**Testing:** Después de 8 días → ¿registros incompletos eliminados? Mismo participante con 5 sesiones → ¿solo quedan 3?

---

### Fix 3 — Índices Faltantes en la Tabla

**Archivo:** `admin/database-schema-manager.php`

**Cambio:** Agregar verificación y creación de índices en método de reparación

**Código:**
```php
// Verificar y agregar índices si no existen
$indexes_to_add = [
    'idx_participant_completed' => "ALTER TABLE {$table} ADD INDEX idx_participant_completed (participant_id, completed)",
    'idx_updated_completed'     => "ALTER TABLE {$table} ADD INDEX idx_updated_completed (updated_at, completed)",
    'idx_form_participant'      => "ALTER TABLE {$table} ADD INDEX idx_form_participant (form_id, participant_id)",
];

$existing_indexes = $wpdb->get_col("SHOW INDEX FROM {$table}", 2);

foreach ($indexes_to_add as $index_name => $sql) {
    if (!in_array($index_name, $existing_indexes)) {
        $wpdb->query($sql);
    }
}
```

**Testing:** `SHOW INDEX FROM wp_eipsi_partial_responses` → ¿aparecen los 3 índices nuevos?

---

## FASE 2 - Prioridad MEDIA (Semana 2)

### Fix 4 — Fallback Criptográficamente Seguro para participant_id

**Archivo:** `src/frontend/eipsi-save-continue.js`

**Cambio:** Reemplazar generación actual por:

```javascript
generateSecureParticipantId() {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return 'p-' + crypto.randomUUID().replace(/-/g, '').substring(0, 12);
    }
    // Fallback criptográficamente seguro
    if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
        const array = new Uint8Array(8);
        crypto.getRandomValues(array);
        return 'p-' + Array.from(array, b => b.toString(16).padStart(2, '0')).join('').substring(0, 12);
    }
    // Último fallback — Math.random pero con timestamp
    return 'p-' + Date.now().toString(36) + Math.random().toString(36).substring(2, 8);
}
```

---

### Fix 5 — Dirty Tracking para Reducir Payload

**Archivo:** `src/frontend/eipsi-save-continue.js`

**Cambio:** Solo enviar campos modificados, no todo el formulario

```javascript
// En constructor:
this.dirtyFields = new Set();
this.lastSavedChecksum = null;

// En el handler de input:
handleFieldChange(field) {
    this.dirtyFields.add(field.name || field.id);
    this.debouncedSave();
}

// En collectResponses():
collectResponses(fullScan = false) {
    const responses = {};
    
    if (fullScan || this.dirtyFields.size === 0) {
        // Scan completo — para beforeunload y primer guardado
        const formData = new FormData(this.form);
        formData.forEach((value, key) => {
            if (!this.isInternalField(key)) {
                responses[key] = value;
            }
        });
    } else {
        // Solo campos modificados
        this.dirtyFields.forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) responses[fieldName] = field.value;
        });
    }
    
    return responses;
}

// Limpiar dirty tracking después de cada save exitoso:
async executeSave(trigger) {
    const responses = this.collectResponses(trigger === 'beforeunload');
    const success = await this.saveToServer(responses);
    if (success) {
        this.dirtyFields.clear();
    }
}
```

---

### Fix 6 — Merge Inteligente Local vs Server

**Archivo:** `src/frontend/eipsi-save-continue.js`

**Cambio:** Reemplazar `checkForPartialResponse()` por versión con paralelismo

```javascript
async checkForPartialResponse() {
    // Cargar ambos en paralelo para reducir latencia
    const [serverPartial, localPartial] = await Promise.all([
        this.loadFromServer().catch(() => null),
        this.loadFromIDB().catch(() => null)
    ]);
    
    const hasServer = serverPartial?.found && serverPartial?.partial;
    const hasLocal  = !!localPartial;
    
    if (!hasServer && !hasLocal) return; // Nada que recuperar
    
    let toRestore;
    
    if (hasServer && hasLocal) {
        // Usar el más reciente basándose en timestamp
        const serverDate = new Date(serverPartial.partial.updated_at || 0);
        const localDate  = new Date(localPartial.updated_at || localPartial.savedAt || 0);
        
        toRestore = localDate > serverDate ? localPartial : serverPartial.partial;
        
        // Si local es más reciente, sincronizar con servidor en background
        if (localDate > serverDate) {
            this.saveToServer(localPartial.responses, localPartial.page_index)
                .catch(e => console.warn('[EIPSI] Background sync failed:', e));
        }
    } else {
        toRestore = hasServer ? serverPartial.partial : localPartial;
    }
    
    this.showRecoveryPopup(toRestore);
}
```

**Testing:** Cerrar pestaña y abrir nueva → ¿aparece popup de recuperación? ¿Elige el más reciente correctamente?

---

## FASE 3 - Prioridad MEDIA (Semana 3)

### Fix 7 — Circuit Breaker para AJAX

**Archivo:** `src/frontend/eipsi-save-continue.js`

**Cambio:** Nueva clase + integración

```javascript
class EipsiCircuitBreaker {
    constructor(threshold = 5, timeout = 60000) {
        this.failures  = 0;
        this.threshold = threshold;
        this.timeout   = timeout;
        this.state     = 'CLOSED';
        this.nextAttempt = null;
    }
    
    async execute(fn) {
        if (this.state === 'OPEN') {
            if (Date.now() < this.nextAttempt) {
                throw new Error('Circuit OPEN — servidor no disponible');
            }
            this.state = 'HALF_OPEN';
        }
        
        try {
            const result = await fn();
            this.onSuccess();
            return result;
        } catch (error) {
            this.onFailure();
            throw error;
        }
    }
    
    onSuccess() {
        this.failures = 0;
        this.state    = 'CLOSED';
    }
    
    onFailure() {
        this.failures++;
        if (this.failures >= this.threshold) {
            this.state       = 'OPEN';
            this.nextAttempt = Date.now() + this.timeout;
            console.warn('[EIPSI] Circuit breaker abierto — demasiados errores de red');
        }
    }
    
    isOpen() { return this.state === 'OPEN'; }
}
```

**Integración en constructor:**
```javascript
this.circuitBreaker = new EipsiCircuitBreaker(5, 60000);
```

**Modificación de saveToServer():**
```javascript
async saveToServer(responses, pageIndex) {
    if (this.circuitBreaker.isOpen()) {
        // Guardar solo en IndexedDB mientras servidor no disponible
        await this.saveToIDB(responses, pageIndex);
        return false;
    }
    
    return this.circuitBreaker.execute(async () => {
        // Lógica actual de saveToServer
    });
}
```

**Testing:** 6 errores consecutivos de red → ¿circuit breaker abre y deja de intentar?

---

### Fix 8 — Verificación de Integridad de IndexedDB

**Archivo:** `src/frontend/eipsi-save-continue.js`

**Cambio:** En método `openDB()`, agregar verificación de integridad

```javascript
async openDB() {
    try {
        const db = await this.initIndexedDB();
        // Verificar integridad haciendo una lectura simple
        await db.transaction(['responses'], 'readonly')
                .objectStore('responses')
                .get('test-key');
        return db;
    } catch (error) {
        console.warn('[EIPSI] IndexedDB corrupto, recreando...', error);
        try {
            // Eliminar DB corrupta y recrear
            await new Promise((resolve, reject) => {
                const deleteReq = indexedDB.deleteDatabase(this.dbName);
                deleteReq.onsuccess = resolve;
                deleteReq.onerror   = reject;
            });
            return await this.initIndexedDB();
        } catch (resetError) {
            console.error('[EIPSI] No se pudo recuperar IndexedDB:', resetError);
            return null; // Fallback a solo servidor
        }
    }
}
```

---

## FASE 4 - Indicador Visual de Guardado (Semana 3-4)

### Frontend: Métodos de Indicador

**Archivo:** `src/frontend/eipsi-save-continue.js`

```javascript
showSavingIndicator() {
    const indicator = document.querySelector('.eipsi-save-indicator');
    if (indicator) {
        indicator.textContent = '💾 Guardando...';
        indicator.className = 'eipsi-save-indicator saving';
    }
}

showSavedIndicator() {
    const indicator = document.querySelector('.eipsi-save-indicator');
    if (indicator) {
        indicator.textContent = '✓ Guardado';
        indicator.className = 'eipsi-save-indicator saved';
        setTimeout(() => {
            indicator.textContent = '';
            indicator.className = 'eipsi-save-indicator';
        }, 3000);
    }
}

showSaveErrorIndicator() {
    const indicator = document.querySelector('.eipsi-save-indicator');
    if (indicator) {
        indicator.textContent = '⚠️ Sin conexión — guardado local';
        indicator.className = 'eipsi-save-indicator error';
    }
}
```

### CSS: Estilos del Indicador

**Archivo:** `assets/css/eipsi-forms.css`

```css
.eipsi-save-indicator {
    font-size: 12px;
    color: #64748b;
    transition: all 0.3s ease;
    min-height: 20px;
}
.eipsi-save-indicator.saving { color: #3b82f6; }
.eipsi-save-indicator.saved  { color: #22c55e; }
.eipsi-save-indicator.error  { color: #f59e0b; }
```

### HTML: Elemento en Template

```html
<span class="eipsi-save-indicator" aria-live="polite"></span>
```

**Testing:** Desconectar red y escribir → ¿indicador muestra "Sin conexión"? Reconectar red → ¿datos guardados en IndexedDB se sincronizan?

---

## CHECKLIST TESTING FINAL

Al terminar todas las fases, reportar resultado de:

- [ ] Navegar rápidamente entre páginas mientras escribe → ¿se pierden datos?
- [ ] Cerrar pestaña y abrir nueva → ¿aparece popup de recuperación?
- [ ] Desconectar red y escribir → ¿indicador muestra "Sin conexión"?
- [ ] Reconectar red → ¿datos guardados en IndexedDB se sincronizan?
- [ ] 6 errores consecutivos de red → ¿circuit breaker abre y deja de intentar?
- [ ] Tabla `eipsi_partial_responses` después de 8 días → ¿registros incompletos eliminados?
- [ ] Mismo participante con 5 sesiones → ¿solo quedan 3 después del cleanup?
- [ ] `SHOW INDEX FROM wp_eipsi_partial_responses` → ¿aparecen los 3 índices nuevos?

---

## RESUMEN DE IMPACTO ESPERADO

| Categoría | Puntaje Actual | Puntaje Esperado |
|-----------|---------------|------------------|
| Seguridad | 6/10 | 9/10 |
| Performance | 7/10 | 9/10 |
| Robustez | 7/10 | 9/10 |
| UX | 8/10 | 9/10 |
| Mantenibilidad | 8/10 | 9/10 |
| **Total** | **7.2/10** | **9.0/10** |

---

## ARCHIVOS A MODIFICAR (Resumen)

1. `src/frontend/eipsi-save-continue.js` — 8 fixes (queue, dirty tracking, circuit breaker, etc.)
2. `eipsi-forms.php` — Cron job para cleanup
3. `admin/database-schema-manager.php` — Índices DB
4. `assets/css/eipsi-forms.css` — Estilos indicador
5. Templates de formulario — Agregar elemento indicador

---

**EIPSI Forms Save & Continue v2.0**  
Estado: Listo para implementación  
Duración estimada: 3-4 semanas  
Próximo paso: Iniciar Fase 1 (Fix 1, 2, 3)
