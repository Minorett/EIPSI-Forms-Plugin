# 🔍 Test de Sincronización Frontend ↔ Backend

Este documento te guía para verificar que el frontend responde correctamente a los cambios del backend.

---

## ✅ **Test 1: Wave Skipping (Auto-Skip de Waves Expiradas)**

### **Objetivo**
Verificar que cuando el backend marca una wave como `skipped`, el frontend la omite y muestra la siguiente.

### **Pasos**

1. **Preparación**:
   - Ir a phpMyAdmin o Adminer
   - Abrir tabla `wp_survey_assignments`
   - Buscar un participante con T2 pendiente

2. **Simular Backend Auto-Skip**:
   ```sql
   -- Marcar T2 como 'skipped' manualmente
   UPDATE wp_survey_assignments 
   SET status = 'skipped' 
   WHERE participant_id = 153 
   AND wave_id = 178;
   ```

3. **Verificar Frontend**:
   - Ir al dashboard del participante 153
   - **Resultado esperado**: El dashboard debería mostrar T3, NO T2
   - **Si muestra T2**: El frontend NO está leyendo el status correctamente

4. **Limpiar**:
   ```sql
   -- Restaurar a pending
   UPDATE wp_survey_assignments 
   SET status = 'pending' 
   WHERE participant_id = 153 
   AND wave_id = 178;
   ```

---

## ✅ **Test 2: T1-Anchor Countdown (Waves Bloqueadas)**

### **Objetivo**
Verificar que el frontend muestra el countdown cuando `available_at` está en el futuro.

### **Pasos**

1. **Preparación**:
   - Completar T1 de un participante
   - Verificar que T2 tiene `available_at` en el futuro

2. **Verificar en Base de Datos**:
   ```sql
   SELECT 
       p.email,
       w.wave_name,
       a.status,
       a.available_at,
       NOW() as current_time,
       TIMESTAMPDIFF(MINUTE, NOW(), a.available_at) as minutes_until_available
   FROM wp_survey_assignments a
   JOIN wp_survey_participants p ON a.participant_id = p.id
   JOIN wp_survey_waves w ON a.wave_id = w.id
   WHERE a.participant_id = 153
   ORDER BY w.wave_index;
   ```

3. **Verificar Frontend**:
   - Ir al dashboard del participante
   - **Resultado esperado**: 
     - Si `available_at` > NOW: Debería mostrar countdown
     - Si `available_at` < NOW: Debería mostrar "Comenzar toma"
   - **Si NO muestra countdown**: El frontend NO está leyendo `available_at`

---

## ✅ **Test 3: Wave Completion (Marcar como Submitted)**

### **Objetivo**
Verificar que cuando completás una wave, el frontend actualiza el progreso.

### **Pasos**

1. **Estado Inicial**:
   - Anotar el progreso actual (ej: "33% completado")

2. **Completar Wave**:
   - Completar T1 o cualquier wave pendiente
   - Verificar logs:
     ```
     [Wave_Service] Assignment marcado como submitted
     [EIPSI T1-Anchor] Successfully recalculated 3 waves
     ```

3. **Verificar Frontend**:
   - Recargar el dashboard (F5)
   - **Resultado esperado**: 
     - Progreso aumentó (ej: "33%" → "66%")
     - Wave completada muestra ✓
     - Próxima wave se muestra
   - **Si NO actualiza**: Problema de caché o frontend no lee status

---

## ✅ **Test 4: Real-Time Updates (Sin Recargar Página)**

### **Objetivo**
Verificar si el frontend actualiza automáticamente sin F5.

### **Pasos**

1. **Abrir Dashboard**:
   - Dejar abierto el dashboard de un participante

2. **Cambiar Status en Backend**:
   ```sql
   -- Marcar T2 como skipped
   UPDATE wp_survey_assignments 
   SET status = 'skipped' 
   WHERE participant_id = 153 
   AND wave_id = 178;
   ```

3. **Verificar Frontend**:
   - **SIN recargar la página**, esperar 5-10 segundos
   - **Resultado esperado**: 
     - Si hay polling/websockets: Debería actualizar automáticamente
     - Si NO hay polling: Necesitarás F5 para ver cambios
   - **Conclusión**: Si necesita F5, es normal (no hay polling implementado)

---

## 🔧 **Test 5: Diagnóstico de Queries**

### **Objetivo**
Verificar qué queries ejecuta el frontend al cargar el dashboard.

### **Pasos**

1. **Activar Query Log**:
   - Agregar en `wp-config.php`:
     ```php
     define('SAVEQUERIES', true);
     ```

2. **Cargar Dashboard**:
   - Ir al dashboard de un participante

3. **Ver Queries**:
   - Agregar al final de `longitudinal-study-display.php`:
     ```php
     if (defined('SAVEQUERIES') && SAVEQUERIES) {
         global $wpdb;
         error_log('=== QUERIES EJECUTADAS ===');
         foreach ($wpdb->queries as $query) {
             if (strpos($query[0], 'survey_assignments') !== false) {
                 error_log($query[0]);
             }
         }
     }
     ```

4. **Verificar Logs**:
   - Buscar en logs las queries que leen `survey_assignments`
   - **Resultado esperado**: Deberías ver:
     ```sql
     SELECT a.wave_id, a.status FROM wp_survey_assignments a ...
     SELECT available_at, status FROM wp_survey_assignments WHERE ...
     ```

---

## 📊 **Checklist de Verificación**

| Test | Backend Funciona | Frontend Lee Correctamente | Status |
|------|------------------|---------------------------|--------|
| Wave Skipping | ✅ | ❓ | Probar |
| T1-Anchor Countdown | ✅ | ❓ | Probar |
| Wave Completion | ✅ | ❓ | Probar |
| Real-Time Updates | N/A | ❓ | Probar |
| Query Logging | N/A | ❓ | Probar |

---

## 🎯 **Resultado Esperado**

Si todo funciona correctamente:

1. ✅ **Wave Skipping**: Frontend omite waves con `status='skipped'`
2. ✅ **T1-Anchor**: Frontend muestra countdown cuando `available_at` > NOW
3. ✅ **Wave Completion**: Frontend actualiza progreso cuando `status='submitted'`
4. ⚠️ **Real-Time**: Probablemente necesite F5 (no hay polling)
5. ✅ **Queries**: Frontend ejecuta queries correctas

---

## 🐛 **Problemas Comunes**

### **Problema 1: Frontend no actualiza después de completar wave**
- **Causa**: Caché de WordPress o navegador
- **Solución**: 
  1. Desactivar caché de WordPress
  2. Ctrl+Shift+R en navegador (hard refresh)
  3. Verificar que no haya `wp_cache_get()` en el código

### **Problema 2: Countdown no aparece**
- **Causa**: `available_at` no está en la base de datos
- **Solución**: 
  1. Verificar que T1 se completó correctamente
  2. Verificar logs: `[EIPSI T1-Anchor] Successfully recalculated`
  3. Verificar query: `SELECT available_at FROM wp_survey_assignments`

### **Problema 3: Wave skipped no se omite**
- **Causa**: Frontend no lee el status
- **Solución**: 
  1. Verificar línea 100 de `longitudinal-study-display.php`
  2. Debería tener: `! in_array( $status, array( 'expired', 'skipped' ) )`

---

## 📝 **Cómo Reportar Resultados**

Para cada test, reportá:

```
Test: [nombre del test]
Resultado: [✅ PASS / ❌ FAIL]
Detalles: [qué viste en el frontend]
Logs: [copiar logs relevantes]
Screenshots: [si es posible]
```

Ejemplo:
```
Test: Wave Skipping
Resultado: ✅ PASS
Detalles: Marqué T2 como 'skipped', el dashboard mostró T3 correctamente
Logs: [EIPSI-DISPLAY] T1-Anchor: wave_id=179, available_at=2026-05-10 13:53:03
Screenshots: [adjuntar]
```
