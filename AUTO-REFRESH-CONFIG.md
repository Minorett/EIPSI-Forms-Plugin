# 🔄 Sistema de Auto-Refresh Inteligente

## 📋 **Descripción**

El sistema de auto-refresh detecta automáticamente cambios en el estado de las waves y refresca la página cuando es necesario, eliminando la necesidad de que el participante haga F5 manualmente.

---

## ✨ **Características**

### **1. Auto-Refresh cuando Countdown llega a 0**
- ✅ Detecta cuando el countdown de una wave bloqueada llega a 0
- ✅ Muestra notificación: "¡El tiempo de espera terminó!"
- ✅ Refresca automáticamente la página para mostrar la wave desbloqueada

### **2. Polling Periódico de Estado**
- ✅ Verifica cada 30 segundos si el estado cambió en el backend
- ✅ Detecta cambios en:
  - Status de la wave (pending → skipped, etc.)
  - Wave bloqueada → desbloqueada
  - Wave actual → siguiente wave disponible

### **3. Notificación al Usuario**
- ✅ Muestra notificación elegante antes de refrescar
- ✅ Permite refrescar inmediatamente con botón "Actualizar ahora"
- ✅ Auto-refresca después de 3 segundos si el usuario no hace nada

---

## ⚙️ **Configuración**

### **Parámetros Ajustables**

Editar en `assets/js/participant-dashboard-auto-refresh.js`:

```javascript
config: {
    pollInterval: 30000,              // Verificar cada 30 segundos (30000ms)
    countdownCheckInterval: 1000,     // Verificar countdown cada 1 segundo
    autoRefreshDelay: 3000,           // Esperar 3 segundos antes de auto-refresh
    showNotification: true,           // Mostrar notificación antes de refrescar
}
```

### **Ajustes Recomendados**

| Escenario | pollInterval | Razón |
|-----------|--------------|-------|
| **Producción (Recomendado)** | 30000 (30s) | Balance entre UX y carga del servidor |
| **Testing Rápido** | 10000 (10s) | Ver cambios más rápido durante pruebas |
| **Bajo Tráfico** | 60000 (60s) | Reducir carga si hay pocos usuarios |
| **Alto Tráfico** | 45000 (45s) | Evitar sobrecarga del servidor |

⚠️ **NO usar menos de 10 segundos** en producción (puede sobrecargar el servidor)

---

## 🎯 **Casos de Uso**

### **Caso 1: Countdown llega a 0**

**Escenario**:
- Participante completa T1
- T2 está bloqueada por 21 minutos
- Participante deja la pestaña abierta

**Comportamiento**:
1. ⏳ Countdown muestra: "Quedan 21m"
2. ⏳ Countdown actualiza cada segundo: "20m 59s", "20m 58s"...
3. ⏳ Cuando llega a 0:
   - 🔔 Notificación: "¡El tiempo de espera terminó! Actualizando página..."
   - 🔄 Auto-refresh inmediato
4. ✅ Página recarga, T2 ahora muestra "Comenzar toma"

### **Caso 2: Wave es skipped por el backend**

**Escenario**:
- Participante ve T2 como próxima wave
- Backend ejecuta cron y marca T2 como `skipped` (expiró)
- T3 ahora es la próxima wave disponible

**Comportamiento**:
1. 🔍 Polling verifica estado cada 30s
2. 🔍 Detecta: `T2 status = 'skipped'`, `next_wave_id = T3`
3. 🔔 Notificación: "Esta ola fue omitida. Mostrando siguiente ola..."
4. 🔄 Auto-refresh después de 3 segundos
5. ✅ Página recarga, ahora muestra T3

### **Caso 3: Wave se desbloquea (T1-Anchor)**

**Escenario**:
- Participante completa T1 en otra pestaña/dispositivo
- T2 se desbloquea automáticamente
- Participante tiene abierta la pestaña del dashboard

**Comportamiento**:
1. 🔍 Polling verifica estado cada 30s
2. 🔍 Detecta: `is_locked = false` (antes era `true`)
3. 🔔 Notificación: "¡La ola ya está disponible!"
4. 🔄 Auto-refresh después de 3 segundos
5. ✅ Página recarga, T2 muestra "Comenzar toma"

---

## 🔧 **Cómo Funciona Técnicamente**

### **1. Frontend (JavaScript)**

```javascript
// Extrae estado actual de la página
extractCurrentState() {
    // Lee data-wave-id y data-wave-status del HTML
    this.state.currentWaveId = $('.next-wave').data('wave-id');
    this.state.currentWaveStatus = $('.next-wave').data('wave-status');
    
    // Lee timestamp del countdown
    this.state.countdownTarget = $('.eipsi-countdown').data('target-timestamp');
}

// Verifica estado en backend cada 30s
checkBackendState() {
    $.ajax({
        action: 'eipsi_check_wave_state',
        wave_id: this.state.currentWaveId
    });
}

// Compara estado frontend vs backend
handleStateResponse(data) {
    if (data.status !== this.state.currentWaveStatus) {
        this.triggerRefresh('Estado cambió');
    }
}
```

### **2. Backend (PHP)**

```php
// AJAX handler: admin/ajax-wave-state-checker.php
function eipsi_ajax_check_wave_state() {
    // 1. Obtener participant_id autenticado
    $participant_id = EIPSI_Auth_Service::get_current_participant();
    
    // 2. Leer assignment de la base de datos
    $assignment = $wpdb->get_row("
        SELECT status, available_at, due_at
        FROM wp_survey_assignments
        WHERE participant_id = $participant_id AND wave_id = $wave_id
    ");
    
    // 3. Verificar si está bloqueada
    $is_locked = strtotime($assignment->available_at) > time();
    
    // 4. Verificar si fue skipped
    $was_skipped = in_array($assignment->status, ['skipped', 'expired']);
    
    // 5. Retornar estado actual
    return [
        'status' => $assignment->status,
        'is_locked' => $is_locked,
        'was_skipped' => $was_skipped,
        'next_wave_id' => $next_wave_id
    ];
}
```

### **3. Flujo Completo**

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Participante carga dashboard                            │
│    - Frontend extrae: wave_id=178, status='pending'        │
│    - Frontend extrae: countdown_target=1715356323          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. Polling cada 30s                                         │
│    - Frontend: AJAX → eipsi_check_wave_state               │
│    - Backend: Lee DB → status='pending', is_locked=true    │
│    - Frontend: Compara → Sin cambios, no hace nada         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Backend cambia estado (cron marca como skipped)         │
│    - DB: UPDATE status='skipped' WHERE wave_id=178         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Siguiente polling (30s después)                          │
│    - Frontend: AJAX → eipsi_check_wave_state               │
│    - Backend: Lee DB → status='skipped', next_wave_id=179  │
│    - Frontend: Compara → ¡CAMBIO DETECTADO!                │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. Auto-refresh                                             │
│    - Muestra notificación: "Wave omitida..."               │
│    - Espera 3 segundos                                      │
│    - window.location.reload()                               │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. Página recargada                                         │
│    - Ahora muestra T3 (wave_id=179)                        │
│    - Usuario ve la wave correcta sin hacer F5              │
└─────────────────────────────────────────────────────────────┘
```

---

## 🐛 **Debugging**

### **Ver Logs en Consola**

Abrir DevTools (F12) → Console:

```
[EIPSI Auto-Refresh] Initializing...
[EIPSI Auto-Refresh] Initialized {currentWaveId: 178, currentWaveStatus: "pending", countdownTarget: 1715356323}
[EIPSI Auto-Refresh] Starting polling (every 30s)
[EIPSI Auto-Refresh] Checking backend state...
[EIPSI Auto-Refresh] Backend state: {status: "pending", is_locked: true, was_skipped: false}
[EIPSI Auto-Refresh] Status changed: pending → skipped
[EIPSI Auto-Refresh] Triggering refresh: Estado de la ola cambió: skipped
[EIPSI Auto-Refresh] Refreshing page...
```

### **Forzar Refresh Manual**

En consola del navegador:

```javascript
// Ver estado actual
window.EIPSIAutoRefresh.state

// Forzar refresh inmediato
window.EIPSIAutoRefresh.triggerRefresh('Test manual', true)

// Detener polling (para debugging)
window.EIPSIAutoRefresh.stop()

// Reiniciar
window.EIPSIAutoRefresh.init()
```

### **Ver Logs del Backend**

En `wp-content/debug.log`:

```
[EIPSI State Check] participant=153, wave=178, status=pending, locked=yes, skipped=no
[EIPSI State Check] participant=153, wave=178, status=skipped, locked=no, skipped=yes
```

---

## 📊 **Impacto en Rendimiento**

### **Carga del Servidor**

| Usuarios Activos | Requests/min | Carga Estimada |
|------------------|--------------|----------------|
| 10 usuarios | 20 req/min | Muy baja |
| 50 usuarios | 100 req/min | Baja |
| 100 usuarios | 200 req/min | Media |
| 500 usuarios | 1000 req/min | Alta |

**Optimización**: El AJAX handler es muy ligero (1 query SQL), no debería causar problemas.

### **Carga del Cliente**

- ✅ **Muy baja**: Solo 1 AJAX request cada 30s
- ✅ **No afecta UX**: Polling en background
- ✅ **No consume batería**: Intervalo razonable

---

## ⚠️ **Limitaciones**

1. **No es tiempo real**: Hay hasta 30s de delay entre cambio y detección
2. **Requiere pestaña abierta**: Si el usuario cierra la pestaña, no funciona
3. **No funciona offline**: Requiere conexión a internet

---

## 🎛️ **Desactivar Auto-Refresh**

Si querés desactivarlo temporalmente:

```javascript
// En participant-dashboard-auto-refresh.js, línea 14:
config: {
    pollInterval: 30000,
    countdownCheckInterval: 1000,
    autoRefreshDelay: 3000,
    showNotification: false,  // ← Cambiar a false para refrescar sin notificación
}
```

O comentar el enqueue en `eipsi-forms.php` líneas 782-798.

---

## ✅ **Testing Checklist**

- [ ] Countdown llega a 0 → auto-refresh
- [ ] Wave marcada como skipped → auto-refresh
- [ ] Wave desbloqueada → auto-refresh
- [ ] Notificación aparece correctamente
- [ ] Botón "Actualizar ahora" funciona
- [ ] Logs en consola muestran polling
- [ ] No hay errores en consola
- [ ] No sobrecarga el servidor

---

## 📝 **Notas Adicionales**

- El sistema solo se activa en páginas con `[eipsi_longitudinal_study]` o `[eipsi_participant_dashboard]`
- Compatible con todos los navegadores modernos (Chrome, Firefox, Safari, Edge)
- No interfiere con otros scripts de WordPress
- Usa jQuery (ya incluido en WordPress)
